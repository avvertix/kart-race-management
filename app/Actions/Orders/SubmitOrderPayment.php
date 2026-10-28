<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;

class SubmitOrderPayment
{
    /**
     * Store the payment proof of the Order. Rejected proofs are kept.
     *
     * @throws ValidationException when the order is not awaiting payment or the proof is not valid
     */
    public function __invoke(Order $order, UploadedFile $proof): Payment
    {
        $notAwaitingPayment = $order->status === OrderStatus::AwaitingPrice
            ? __('The organizer has not set the price yet.')
            : __('The order is not awaiting a payment.');

        if ($order->status !== OrderStatus::AwaitingPayment) {
            throw ValidationException::withMessages(['proof' => $notAwaitingPayment]);
        }

        Validator::make(['proof' => $proof], [
            'proof' => [
                'required',
                File::types(['pdf', 'jpg', 'png'])
                    ->min(1)
                    ->max(10 * 1024),
            ],
        ])->validate();

        $path = $proof->store('', 'payments');

        if ($path === false) {
            throw ValidationException::withMessages([
                'proof' => __('The payment proof could not be saved, please try again.'),
            ]);
        }

        try {
            return DB::transaction(function () use ($order, $path, $notAwaitingPayment) {
                $locked = $order->lockInStatus([OrderStatus::AwaitingPayment], 'proof', $notAwaitingPayment);

                $payment = $locked->payments()->create([
                    'path' => $path,
                    'hash' => hash_file('sha512', Storage::disk('payments')->path($path)),
                ]);

                $locked->update(['status' => OrderStatus::PaymentSubmitted]);

                return $payment;
            });
        } catch (ValidationException $exception) {
            Storage::disk('payments')->delete($path);

            throw $exception;
        }
    }
}

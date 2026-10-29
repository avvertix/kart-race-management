<?php

declare(strict_types=1);

namespace App\Actions;

use App\Support\Payable;
use App\Models\Payment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;

class SubmitPaymentProof
{
    /**
     * Store the payment proof of an Order or a Licence Request. Rejected proofs are kept.
     *
     * @throws ValidationException when no payment is expected or the proof is not valid
     */
    public function __invoke(Payable $payable, UploadedFile $proof): Payment
    {
        DB::transaction(fn () => $payable->lockAwaitingPayment('proof'));

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
            return DB::transaction(function () use ($payable, $path) {
                $locked = $payable->lockAwaitingPayment('proof');

                $payment = $locked->payments()->create([
                    'path' => $path,
                    'hash' => hash_file('sha512', Storage::disk('payments')->path($path)),
                ]);

                $locked->markPaymentSubmitted();

                return $payment;
            });
        } catch (ValidationException $exception) {
            Storage::disk('payments')->delete($path);

            throw $exception;
        }
    }
}

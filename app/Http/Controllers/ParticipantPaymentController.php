<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Participant;
use App\Models\Payment;
use App\Models\PaymentChannelType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;

class ParticipantPaymentController extends Controller
{
    /**
     * Send a new email verification notification.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $this->validate($request, [
            'proof' => [
                'required',
                File::types(['pdf', 'jpg', 'png'])
                    ->min(1)
                    ->max(10 * 1024), // 10 MB maximum
            ],
            'participant' => [
                'required',
                'exists:participants,uuid',
            ],
            'p' => [
                'required',
                'string',
            ],
        ]);

        $participant = Participant::whereUuid($validated['participant'])->first();

        if ($participant->signatureContent() !== $validated['p']) {
            throw ValidationException::withMessages([
                'proof' => __('Could not verify the upload.'),
            ]);
        }

        $path = $request->proof->store('', 'payments');

        $participant->payments()->create([
            'path' => $path,
            'hash' => hash_file('sha512', Storage::disk('payments')->path($path)),
        ]);

        $participant->update(['payment_channel' => PaymentChannelType::BANK_TRANSFER]);

        return redirect($participant->qrCodeUrl())->with('status', 'payment-uploaded');
    }

    /**
     * Show the uploaded payment verification.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, Payment $payment)
    {

        return response()
            ->file(Storage::disk('payments')->path($payment->path));
    }
}

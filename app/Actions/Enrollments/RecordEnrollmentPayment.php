<?php

declare(strict_types=1);

namespace App\Actions\Enrollments;

use App\Enums\EnrollmentPaymentStatus;
use App\Enums\PaymentStatus;
use App\Models\Championship;
use App\Models\Enrollment;
use App\Models\PaymentChannelType;
use App\Models\User;
use App\Notifications\EnrollmentPaymentConfirmed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RecordEnrollmentPayment
{
    public function __construct(private GrantEnrollmentEntitlements $grantEnrollmentEntitlements) {}

    /**
     * Record a payment the organizer received for an active Enrollment by cash, credit card or bank transfer, with its
     * amount in cents and no proof: it is confirmed at once. A proof waiting for review must be reviewed first.
     *
     * @throws ValidationException when the amount is missing, the Enrollment is not active or not awaiting a payment
     */
    public function __invoke(Enrollment $enrollment, User $organizer, PaymentChannelType $channel, int $amount): Enrollment
    {
        Validator::make(['amount' => $amount], ['amount' => 'required|integer|min:1'])->validate();

        $recorded = DB::transaction(function () use ($enrollment, $organizer, $channel, $amount) {
            Championship::query()->whereKey($enrollment->championship_id)->lockForUpdate()->firstOrFail();

            $locked = $enrollment->lockAwaitingPayment('payment');

            $locked->payments()->create([
                'amount' => $amount,
                'channel' => $channel,
                'status' => PaymentStatus::Confirmed,
                'recorded_by' => $organizer->getKey(),
                'reviewed_by' => $organizer->getKey(),
                'reviewed_at' => now(),
            ]);

            $locked->refreshPaymentStatus();

            if ($locked->payment_status === EnrollmentPaymentStatus::Paid) {
                $locked->update(['payment_confirmed_by' => $organizer->getKey()]);
            }

            return $locked;
        });

        ($this->grantEnrollmentEntitlements)($recorded);

        $recorded->user->notify(new EnrollmentPaymentConfirmed($recorded));

        return $recorded;
    }
}

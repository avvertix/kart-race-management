<?php

declare(strict_types=1);

namespace App\Actions\Enrollments;

use App\Enums\EnrollmentPaymentStatus;
use App\Models\Enrollment;
use App\Models\User;
use App\Notifications\EnrollmentPaymentConfirmed;
use App\Notifications\EnrollmentPaymentRejected;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewEnrollmentPayment
{
    public function __construct(private GrantEnrollmentEntitlements $grantEnrollmentEntitlements) {}

    /**
     * Confirm the uploaded payment proof of an active Enrollment, or reject it so the driver uploads a new one. Rejected
     * proofs are kept. A confirmed payment grants the option's race fee discount once the Licence exists, and settles the
     * Licence Request the Enrollment covers.
     *
     * @throws ValidationException when no payment proof awaits confirmation
     */
    public function __invoke(Enrollment $enrollment, User $organizer, bool $confirm): Enrollment
    {
        $reviewed = DB::transaction(function () use ($enrollment, $organizer, $confirm) {
            $locked = Enrollment::query()->whereKey($enrollment->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->payment_status !== EnrollmentPaymentStatus::PaymentSubmitted || ! $locked->status->isActive()) {
                throw ValidationException::withMessages(['payment' => __('The enrollment has no payment proof to review.')]);
            }

            $locked->update($confirm
                ? [
                    'payment_status' => EnrollmentPaymentStatus::Paid,
                    'payment_confirmed_by' => $organizer->getKey(),
                    'paid_at' => now(),
                ]
                : ['payment_status' => EnrollmentPaymentStatus::AwaitingPayment]);

            return $locked;
        });

        if ($confirm) {
            ($this->grantEnrollmentEntitlements)($reviewed);
        }

        $reviewed->user->notify($confirm ? new EnrollmentPaymentConfirmed($reviewed) : new EnrollmentPaymentRejected($reviewed));

        return $reviewed;
    }
}

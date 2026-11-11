<?php

declare(strict_types=1);

namespace App\Actions\Enrollments;

use App\Enums\EnrollmentPaymentStatus;
use App\Models\Enrollment;
use App\Models\User;
use App\Notifications\EnrollmentPaymentConfirmed;
use App\Notifications\EnrollmentPaymentRejected;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ReviewEnrollmentPayment
{
    public function __construct(private GrantEnrollmentEntitlements $grantEnrollmentEntitlements) {}

    /**
     * Confirm the uploaded payment proof of an active Enrollment with the amount received, in cents, as read on the bank
     * statement, or reject it so the driver uploads a new one. Rejected proofs are kept. The Enrollment is paid once its
     * confirmed records reach its amount: that grants the option's race fee discount once the Licence exists, and
     * settles the Licence Request the Enrollment covers.
     *
     * @throws ValidationException when no payment proof awaits confirmation, or confirming without an amount
     */
    public function __invoke(Enrollment $enrollment, User $organizer, bool $confirm, ?int $amount = null): Enrollment
    {
        if ($confirm) {
            Validator::make(['amount' => $amount], ['amount' => 'required|integer|min:1'])->validate();
        }

        $reviewed = DB::transaction(function () use ($enrollment, $organizer, $confirm, $amount) {
            $locked = Enrollment::query()->whereKey($enrollment->getKey())->lockForUpdate()->firstOrFail();

            $proof = $locked->payments()->submitted()->latest('id')->first();

            if ($proof === null || ! $locked->status->isActive()) {
                throw ValidationException::withMessages(['payment' => __('The enrollment has no payment proof to review.')]);
            }

            $proof->review($organizer, $confirm ? $amount : null);

            $locked->refreshPaymentStatus();

            if ($locked->payment_status === EnrollmentPaymentStatus::Paid) {
                $locked->update(['payment_confirmed_by' => $organizer->getKey()]);
            }

            return $locked;
        });

        if ($confirm) {
            ($this->grantEnrollmentEntitlements)($reviewed);
        }

        $reviewed->user->notify($confirm ? new EnrollmentPaymentConfirmed($reviewed) : new EnrollmentPaymentRejected($reviewed));

        return $reviewed;
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Enrollments;

use App\Enums\EnrollmentPaymentStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\LicenceRequestStatus;
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
     * Record a payment the organizer received for an Enrollment by cash, credit card or bank transfer, with its amount
     * in cents and no proof: it is confirmed at once, and unlocks the Enrollment like a confirmed proof. A proof waiting
     * for review must be reviewed first. A withdrawn Enrollment comes back to submitted, see Enrollment::canBeBroughtBack(),
     * while the driver has no other active Enrollment in the championship: it moves to the Licence emitted meanwhile from
     * the request it waited for, or covers that request again when its option covers the renewal. The race numbers
     * released on withdrawal are not restored: the driver or the organizer picks one again.
     *
     * @throws ValidationException when the amount is missing, or the Enrollment can't take a payment
     */
    public function __invoke(Enrollment $enrollment, User $organizer, PaymentChannelType $channel, int $amount): Enrollment
    {
        Validator::make(['amount' => $amount], ['amount' => 'required|integer|min:1'])->validate();

        $recorded = DB::transaction(function () use ($enrollment, $organizer, $channel, $amount) {
            $championship = Championship::query()->whereKey($enrollment->championship_id)->lockForUpdate()->firstOrFail();

            $this->bringBackWhenWithdrawn($championship, $enrollment);

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

    /**
     * Return a withdrawn Enrollment to submitted, refusing it when it can't come back. Call it inside the transaction,
     * after locking the championship.
     *
     * @throws ValidationException
     */
    private function bringBackWhenWithdrawn(Championship $championship, Enrollment $enrollment): void
    {
        $locked = Enrollment::query()->whereKey($enrollment->getKey())->lockForUpdate()->firstOrFail();

        if ($locked->status !== EnrollmentStatus::Withdrawn) {
            return;
        }

        if (! $locked->canBeBroughtBack()) {
            throw ValidationException::withMessages(['payment' => __('The enrollment can\'t come back: the first race started, or what was paid is already race credit.')]);
        }

        $waitedFor = $locked->licenceRequest;

        if ($locked->licence === null && $waitedFor?->status === LicenceRequestStatus::Emitted && $waitedFor->licence !== null) {
            $locked->update(['licence_id' => $waitedFor->licence->getKey(), 'licence_request_id' => null]);
            $locked->refresh();
            $waitedFor = null;
        }

        Enrollment::ensureNotEnrolled($championship, $locked->licence, $locked->licence === null ? $waitedFor : null, $locked);

        $locked->update(['status' => EnrollmentStatus::Submitted]);

        if ($locked->licence === null && $waitedFor !== null && $locked->option?->covers_licence_renewal && $waitedFor->status !== LicenceRequestStatus::Rejected) {
            $waitedFor->coverWith($locked);
        }
    }
}

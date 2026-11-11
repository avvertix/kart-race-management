<?php

declare(strict_types=1);

namespace App\Actions\Enrollments;

use App\Enums\BibReservationStatus;
use App\Enums\EnrollmentPaymentStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\LicenceRequestStatus;
use App\Models\Championship;
use App\Models\Enrollment;
use App\Models\EnrollmentOption;
use App\Models\LicenceRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChangeEnrollmentOption
{
    public function __construct(private GrantEnrollmentEntitlements $grantEnrollmentEntitlements) {}

    /**
     * Move a submitted Enrollment to another option offered by its championship, e.g. from Silver to Gold, until it is
     * paid in full: the driver while enrollment is open, among the options offered to the licence category; the organizer
     * at any time, e.g. when the licence category changed on renewal. The amount, deposit and due date follow the new
     * option, and the payment status is recomputed from the payments already made: paying more than the new amount leaves
     * the difference to refund outside the platform. The Licence Request the Enrollment waits for is covered or uncovered
     * as the new option covers the renewal or not, and race numbers are released when the new option reserves none. A
     * race fee discount already granted stays. A payment proof waiting for review must be reviewed first.
     *
     * @throws ValidationException when the Enrollment is not submitted, paid in full, has a proof to review, or the option is not offered
     */
    public function __invoke(Enrollment $enrollment, EnrollmentOption $option, bool $byDriver = false): Enrollment
    {
        $changed = DB::transaction(function () use ($enrollment, $option, $byDriver) {
            $championship = Championship::query()->whereKey($enrollment->championship_id)->lockForUpdate()->firstOrFail();

            $locked = $enrollment->lockInStatus([EnrollmentStatus::Submitted], 'option', __('Only a submitted enrollment can change option.'));

            if ($byDriver && ! $championship->enrollment->isOpen()) {
                throw ValidationException::withMessages(['option' => __('Enrollment is closed: ask the organizer to change the option.')]);
            }

            $isOffered = EnrollmentOption::query()->whereKey($option->getKey())->where('championship_id', $locked->championship_id)->offered()->exists()
                && (! $byDriver || $option->isFor(SubmitEnrollment::categoryOf($locked->licence, $locked->licenceRequest, null)));

            if (! $isOffered) {
                throw ValidationException::withMessages(['option' => __('The enrollment option is not available.')]);
            }

            if (in_array($locked->payment_status, [EnrollmentPaymentStatus::Paid, EnrollmentPaymentStatus::PaymentSubmitted], true)) {
                throw ValidationException::withMessages(['option' => __('The enrollment is paid in full or a payment proof waits for review: the option can\'t change.')]);
            }

            $locked->update([
                'enrollment_option_id' => $option->getKey(),
                'amount' => $option->amount,
                'currency' => $option->currency,
                'deposit_amount' => $option->deposit_amount,
                'balance_due_on' => $option->balance_due_on,
                'balance_reminded_at' => null,
            ]);

            $locked->refreshPaymentStatus();

            $waitedFor = $locked->licenceRequest;

            if ($waitedFor !== null && $waitedFor->status !== LicenceRequestStatus::Rejected) {
                if ($option->covers_licence_renewal) {
                    $waitedFor->coverWith($locked);
                } elseif ($waitedFor->covering_enrollment_id === $locked->getKey()) {
                    $waitedFor->uncover();
                }
            }

            LicenceRequest::query()
                ->where('covering_enrollment_id', $locked->getKey())
                ->whereKeyNot($waitedFor?->getKey() ?? 0)
                ->open()
                ->get()
                ->when(! $option->covers_licence_renewal, fn ($requests) => $requests->each(fn (LicenceRequest $licenceRequest) => $licenceRequest->uncover()));

            if (! $option->reserves_race_number) {
                $locked->reservations()->notReleased()->update([
                    'status' => BibReservationStatus::Released,
                    'released_at' => now(),
                ]);
            }

            return $locked;
        });

        ($this->grantEnrollmentEntitlements)($changed);

        return $changed;
    }
}

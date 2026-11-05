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
    /**
     * The organizer moves a submitted Enrollment to another option offered by its championship, e.g. when the licence
     * category changed on renewal, until its payment is confirmed. The amount follows the new option, the Licence Request
     * the Enrollment waits for is covered or uncovered as the new option covers the renewal or not, and race numbers are
     * released when the new option reserves none. A payment proof waiting for review must be rejected first.
     *
     * @throws ValidationException when the Enrollment is not submitted, already paid, has a proof to review, or the option is not offered by its championship
     */
    public function __invoke(Enrollment $enrollment, EnrollmentOption $option): Enrollment
    {
        return DB::transaction(function () use ($enrollment, $option) {
            Championship::query()->whereKey($enrollment->championship_id)->lockForUpdate()->firstOrFail();

            $locked = $enrollment->lockInStatus([EnrollmentStatus::Submitted], 'option', __('Only a submitted enrollment can change option.'));

            $isOffered = EnrollmentOption::query()->whereKey($option->getKey())->where('championship_id', $locked->championship_id)->offered()->exists();

            if (! $isOffered) {
                throw ValidationException::withMessages(['option' => __('The enrollment option is not available.')]);
            }

            if (in_array($locked->payment_status, [EnrollmentPaymentStatus::Paid, EnrollmentPaymentStatus::PaymentSubmitted], true)) {
                throw ValidationException::withMessages(['option' => __('The payment is already confirmed or waits for review: the option can\'t change.')]);
            }

            $locked->update([
                'enrollment_option_id' => $option->getKey(),
                'amount' => $option->amount,
                'currency' => $option->currency,
                'payment_status' => $option->isFree() ? EnrollmentPaymentStatus::NotDue : EnrollmentPaymentStatus::AwaitingPayment,
            ]);

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
    }
}

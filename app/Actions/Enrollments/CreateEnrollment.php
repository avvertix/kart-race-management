<?php

declare(strict_types=1);

namespace App\Actions\Enrollments;

use App\Enums\EnrollmentPaymentStatus;
use App\Enums\EnrollmentStatus;
use App\Models\Championship;
use App\Models\Enrollment;
use App\Models\EnrollmentOption;
use App\Models\Licence;
use App\Models\LicenceRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateEnrollment
{
    /**
     * Create a submitted Enrollment for the Licence, or for the Licence Request it waits for, in a championship accepting
     * enrollments, with the Enrollment Option chosen: its amount is copied to the Enrollment's own payment, and an option
     * covering the licence renewal pays the Licence Request too, while it is not paid nor covered yet. Without an option
     * (enrolled by the organizer, or by a Package bought on its own) nothing is due. A Licence Request is locked and
     * checked again, so that an Enrollment never waits for a request emitted meanwhile.
     *
     * @throws ValidationException when the Licence or request is already enrolled, or the option is not of the championship
     */
    public function __invoke(User $user, Championship $championship, ?Licence $licence, ?LicenceRequest $licenceRequest, ?Licence $competitorLicence, ?EnrollmentOption $option = null): Enrollment
    {
        if (! $championship->enrollment->enabled) {
            throw ValidationException::withMessages(['enrollment' => __('The championship does not accept enrollments.')]);
        }

        if ($option !== null && $option->championship_id !== $championship->getKey()) {
            throw ValidationException::withMessages(['option' => __('The enrollment option is not available.')]);
        }

        return DB::transaction(function () use ($user, $championship, $licence, $licenceRequest, $competitorLicence, $option) {
            Championship::query()->whereKey($championship->getKey())->lockForUpdate()->firstOrFail();

            if ($licence === null && $licenceRequest !== null) {
                $isStillOpen = LicenceRequest::query()->whereKey($licenceRequest->getKey())->enrollableIn($championship)->lockForUpdate()->exists();

                if (! $isStillOpen) {
                    throw ValidationException::withMessages(['licence_request' => __('The licence request is no longer open: enroll the licence instead.')]);
                }
            }

            if ($option !== null && EnrollmentOption::query()->whereKey($option->getKey())->offered()->doesntExist()) {
                throw ValidationException::withMessages(['option' => __('The enrollment option is not available.')]);
            }

            Enrollment::ensureNotEnrolled($championship, $licence, $licenceRequest);

            if ($competitorLicence !== null && ! Enrollment::isValidCompetitor($championship, $competitorLicence, $licence->holder ?? $licenceRequest?->holder)) {
                throw ValidationException::withMessages(['competitor_licence' => __('The competitor must be an adult other than the driver.')]);
            }

            $enrollment = $championship->enrollments()->create([
                'licence_id' => $licence?->getKey(),
                'licence_request_id' => $licence === null ? $licenceRequest?->getKey() : null,
                'competitor_licence_id' => $competitorLicence?->getKey(),
                'user_id' => $user->getKey(),
                'status' => EnrollmentStatus::Submitted,
                'enrollment_option_id' => $option?->getKey(),
                'amount' => $option->amount ?? 0,
                'currency' => $option->currency ?? 'EUR',
                'payment_status' => $option === null || $option->isFree() ? EnrollmentPaymentStatus::NotDue : EnrollmentPaymentStatus::AwaitingPayment,
            ]);

            if ($option?->covers_licence_renewal && $licence === null && $licenceRequest !== null) {
                $licenceRequest->coverWith($enrollment);
            }

            return $enrollment;
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Enrollments;

use App\Actions\Orders\PlaceOrder;
use App\Enums\EnrollmentStatus;
use App\Models\Championship;
use App\Models\Enrollment;
use App\Models\Licence;
use App\Models\LicenceRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateEnrollment
{
    public function __construct(private PlaceOrder $placeOrder) {}

    /**
     * Create a submitted Enrollment for the Licence, or for the Licence Request it waits for. When `$pays` is true and the
     * enrollment is not free, it also places the Order for the enrollment Product, owned by the User. A Licence Request is
     * locked and checked again, so that an Enrollment never waits for a request emitted meanwhile.
     *
     * @throws ValidationException when the Licence or request is already enrolled, or the enrollment Product can't be ordered
     */
    public function __invoke(User $user, Championship $championship, ?Licence $licence, ?LicenceRequest $licenceRequest, ?Licence $competitorLicence, bool $pays): Enrollment
    {
        $product = $pays ? $championship->enrollmentProduct() : null;

        if ($pays && ! $championship->enrollment->isFree() && $product === null) {
            throw ValidationException::withMessages(['enrollment' => __('The enrollment is not available at the moment.')]);
        }

        return DB::transaction(function () use ($user, $championship, $licence, $licenceRequest, $competitorLicence, $product) {
            Championship::query()->whereKey($championship->getKey())->lockForUpdate()->firstOrFail();

            if ($licence === null && $licenceRequest !== null) {
                $isStillOpen = LicenceRequest::query()->whereKey($licenceRequest->getKey())->enrollableIn($championship)->lockForUpdate()->exists();

                if (! $isStillOpen) {
                    throw ValidationException::withMessages(['licence_request' => __('The licence request is no longer open: enroll the licence instead.')]);
                }
            }

            Enrollment::ensureNotEnrolled($championship, $licence, $licenceRequest);

            $order = $product === null ? null : ($this->placeOrder)($user, $product);

            return $championship->enrollments()->create([
                'licence_id' => $licence?->getKey(),
                'licence_request_id' => $licence === null ? $licenceRequest?->getKey() : null,
                'competitor_licence_id' => $competitorLicence?->getKey(),
                'user_id' => $user->getKey(),
                'status' => EnrollmentStatus::Submitted,
                'order_id' => $order?->getKey(),
            ]);
        });
    }
}

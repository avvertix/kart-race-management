<?php

declare(strict_types=1);

namespace App\Actions\Enrollments;

use App\Actions\Orders\PlaceOrder;
use App\Enums\EnrollmentStatus;
use App\Enums\LicenceRequestPaymentStatus;
use App\Models\Championship;
use App\Models\Enrollment;
use App\Models\Licence;
use App\Models\LicenceRequest;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateEnrollment
{
    public function __construct(private PlaceOrder $placeOrder) {}

    /**
     * Create a submitted Enrollment for the Licence, or for the Licence Request it waits for, in a championship accepting
     * enrollments. When `$pays` is true it also places the Order, owned by the User, for the chosen Package including the
     * enrollment, or else for the fixed enrollment fee when not free. A fee covering the licence renewal pays the Licence
     * Request too, while it is not paid nor covered yet. A Licence Request is locked and checked again, so that an Enrollment never waits for a
     * request emitted meanwhile.
     *
     * @throws ValidationException when the Licence or request is already enrolled, or the enrollment Product can't be ordered
     */
    public function __invoke(User $user, Championship $championship, ?Licence $licence, ?LicenceRequest $licenceRequest, ?Licence $competitorLicence, bool $pays, ?Product $package = null): Enrollment
    {
        if (! $championship->enrollment->enabled) {
            throw ValidationException::withMessages(['enrollment' => __('The championship does not accept enrollments.')]);
        }

        $product = $pays ? ($package ?? $championship->enrollmentProduct()) : null;

        if ($pays && $package === null && ! $championship->enrollment->isFree() && ($product === null || $product->orderablePrice()?->isRange() !== false)) {
            throw ValidationException::withMessages(['enrollment' => __('The enrollment is not available at the moment.')]);
        }

        $coversRenewal = $package === null && $product !== null && $championship->enrollment->fee_covers_licence_renewal;

        return DB::transaction(function () use ($user, $championship, $licence, $licenceRequest, $competitorLicence, $product, $coversRenewal) {
            Championship::query()->whereKey($championship->getKey())->lockForUpdate()->firstOrFail();

            if ($licence === null && $licenceRequest !== null) {
                $isStillOpen = LicenceRequest::query()->whereKey($licenceRequest->getKey())->enrollableIn($championship)->lockForUpdate()->exists();

                if (! $isStillOpen) {
                    throw ValidationException::withMessages(['licence_request' => __('The licence request is no longer open: enroll the licence instead.')]);
                }
            }

            Enrollment::ensureNotEnrolled($championship, $licence, $licenceRequest);

            $order = $product === null ? null : ($this->placeOrder)($user, $product);

            if ($coversRenewal && $licence === null && $licenceRequest !== null) {
                $this->coverRenewal($licenceRequest, $order);
            }

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

    /**
     * Let the Order of the fee pay the Licence Request the Enrollment waits for, when its own payment is not made nor
     * covered yet. Call it inside the transaction, with the request locked.
     */
    private function coverRenewal(LicenceRequest $licenceRequest, Order $order): void
    {
        $locked = LicenceRequest::query()->whereKey($licenceRequest->getKey())->lockForUpdate()->firstOrFail();

        if ($locked->covering_order_id !== null || ! in_array($locked->payment_status, [LicenceRequestPaymentStatus::AwaitingPrice, LicenceRequestPaymentStatus::AwaitingPayment], true)) {
            return;
        }

        $locked->update([
            'covering_order_id' => $order->getKey(),
            'payment_status' => LicenceRequestPaymentStatus::Covered,
        ]);
    }
}

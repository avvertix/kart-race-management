<?php

declare(strict_types=1);

namespace App\Actions\LicenceRequests;

use App\Enums\LicenceProvider;
use App\Enums\LicenceRequestPaymentStatus;
use App\Enums\LicenceRequestStatus;
use App\Models\Licence;
use App\Models\LicenceRequest;
use App\Models\Order;
use App\Models\User;
use App\Support\LicenceCatalogue;
use Illuminate\Auth\Access\AuthorizationException;

class StartLicenceRenewal
{
    /**
     * Start a draft renewal, empty or pre-filled from a Licence the User manages: provider, Licence Type while still offered,
     * competitor licence, holder, previous number and, for a licence emitted on the platform, the parents entered then.
     * A request started from a paid Package covering the renewal asks for no payment.
     *
     * @throws AuthorizationException when the Licence is managed by another User
     */
    public function __invoke(User $user, ?Licence $previous = null, ?Order $coveringOrder = null): LicenceRequest
    {
        if ($previous !== null && $previous->user_id !== $user->getKey()) {
            throw new AuthorizationException;
        }

        $licenceType = $previous?->licenceTypeData();

        $isOffered = $licenceType !== null && $licenceType->active;

        return LicenceRequest::create([
            'user_id' => $user->getKey(),
            'status' => LicenceRequestStatus::Draft,
            'payment_status' => $coveringOrder === null ? LicenceRequestPaymentStatus::AwaitingPrice : LicenceRequestPaymentStatus::Covered,
            'covering_order_id' => $coveringOrder?->getKey(),
            'provider' => $previous->provider ?? $this->onlyProvider(),
            'licence_type' => $isOffered ? $licenceType->code : null,
            'includes_competitor' => $isOffered && $previous->includes_competitor,
            'previous_licence_number' => $previous?->number,
            'holder' => $previous?->holder,
            'parents' => array_values($previous?->licenceRequest?->parents?->all() ?? []),
        ]);
    }

    /**
     * The provider to pre-select when only one has Licence Types.
     */
    private function onlyProvider(): ?LicenceProvider
    {
        $providers = LicenceCatalogue::providers();

        return count($providers) === 1 ? $providers[0] : null;
    }
}

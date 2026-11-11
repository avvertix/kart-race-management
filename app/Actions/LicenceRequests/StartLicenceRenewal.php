<?php

declare(strict_types=1);

namespace App\Actions\LicenceRequests;

use App\Enums\LicenceProvider;
use App\Enums\LicenceRequestPaymentStatus;
use App\Enums\LicenceRequestStatus;
use App\Models\Licence;
use App\Models\LicenceRequest;
use App\Models\User;
use App\Support\LicenceCatalogue;
use Illuminate\Auth\Access\AuthorizationException;

class StartLicenceRenewal
{
    /**
     * Start a draft renewal, empty or pre-filled from a Licence the User manages: holder and, for a licence emitted on the
     * platform, the parents entered then; while its provider is still offered, also provider, previous number, competitor
     * licence and Licence Type while still offered. Otherwise the requester chooses the provider, pre-selected only when
     * one provider has Licence Types. It is for the given year, by default the one renewals are open for. An Enrollment
     * whose option covers the renewal covers the request afterwards, see LicenceRequest::coverWith().
     *
     * @throws AuthorizationException when the Licence is managed by another User
     */
    public function __invoke(User $user, ?Licence $previous = null, ?int $validityYear = null): LicenceRequest
    {
        if ($previous !== null && $previous->user_id !== $user->getKey()) {
            throw new AuthorizationException;
        }

        $licenceType = $previous?->licenceTypeData();

        $isOffered = $licenceType !== null && $licenceType->active;

        $isProviderOffered = $previous !== null && in_array($previous->provider, LicenceCatalogue::providers(), true);

        return LicenceRequest::create([
            'user_id' => $user->getKey(),
            'status' => LicenceRequestStatus::Draft,
            'payment_status' => LicenceRequestPaymentStatus::AwaitingPrice,
            'provider' => $isProviderOffered ? $previous->provider : $this->onlyProvider(),
            'validity_year' => $validityYear ?? LicenceRequest::validityYearFor(now()),
            'licence_type' => $isOffered ? $licenceType->code : null,
            'includes_competitor' => $isOffered && $previous->includes_competitor,
            'previous_licence_number' => $isProviderOffered ? $previous->number : null,
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

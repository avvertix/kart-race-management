<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\LicenceProvider;
use App\Enums\LicenceRole;
use App\Models\DriverLicence;

/**
 * A Licence Type from the provider lists in config/licences.php.
 */
final readonly class LicenceTypeData
{
    public function __construct(
        public LicenceProvider $provider,
        public string $code,
        public string $name,
        public LicenceRole $role,
        public ?DriverLicence $raceLicenceCategory = null,
        public bool $active = true,
    ) {}

    /**
     * Whether the licence can include the competitor licence, under the same number: ACI driver licences only.
     */
    public function allowsCompetitor(): bool
    {
        return $this->provider === LicenceProvider::Aci && $this->role === LicenceRole::Driver;
    }

    /**
     * The attributes a Licence of this type stores, copied so the Licence keeps them if the type leaves the list.
     *
     * @return array{provider: LicenceProvider, licence_type: string, includes_competitor: bool, role: LicenceRole, race_licence_category: ?DriverLicence}
     */
    public function licenceAttributes(bool $includesCompetitor): array
    {
        return [
            'provider' => $this->provider,
            'licence_type' => $this->code,
            'includes_competitor' => $includesCompetitor && $this->allowsCompetitor(),
            'role' => $this->role,
            'race_licence_category' => $this->raceLicenceCategory,
        ];
    }
}

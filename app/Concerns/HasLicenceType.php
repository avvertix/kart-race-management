<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Data\LicenceTypeData;
use App\Support\LicenceCatalogue;

/**
 * A model with a provider, a Licence Type code and whether it includes the competitor licence.
 */
trait HasLicenceType
{
    /**
     * The Licence Type from the provider list, null when not picked yet or no longer listed.
     */
    public function licenceTypeData(): ?LicenceTypeData
    {
        return LicenceCatalogue::find($this->provider, $this->licence_type);
    }

    /**
     * The Licence Type as shown to people, e.g. "NAZ E" or "NAZ E with competitor licence". Falls back to the stored code.
     */
    public function licenceTypeLabel(): ?string
    {
        $name = $this->licenceTypeData()->name ?? $this->licence_type;

        if ($name === null) {
            return null;
        }

        return $this->includes_competitor ? __(':type with competitor licence', ['type' => $name]) : $name;
    }
}

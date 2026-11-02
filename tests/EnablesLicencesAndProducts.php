<?php

declare(strict_types=1);

namespace Tests;

use App\Features\LicencesAndProducts;
use Laravel\Pennant\Feature;

/**
 * Turn on the early access to licences and products for everyone, guests included.
 */
trait EnablesLicencesAndProducts
{
    protected function setUpEnablesLicencesAndProducts(): void
    {
        Feature::define(LicencesAndProducts::class, fn () => true);
    }
}

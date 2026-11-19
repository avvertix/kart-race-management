<?php

declare(strict_types=1);

namespace Tests;

use App\Features\LicencesAndEnrollments;
use Laravel\Pennant\Feature;

/**
 * Turn on the early access to licences and enrollments for everyone, guests included.
 */
trait EnablesLicencesAndEnrollments
{
    protected function setUpEnablesLicencesAndEnrollments(): void
    {
        Feature::define(LicencesAndEnrollments::class, fn () => true);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\LicenceLevel;
use App\Enums\LicenceProvider;
use App\Enums\LicenceRole;
use App\Models\DriverLicence;
use Tests\TestCase;

class LicenceEnumsTest extends TestCase
{
    public function test_licence_enum_values_are_stable(): void
    {
        $this->assertEquals(['aci', 'asi'], array_column(LicenceProvider::cases(), 'value'));
        $this->assertEquals(['driver', 'competitor', 'mechanic'], array_column(LicenceRole::cases(), 'value'));
        $this->assertEquals(['national', 'international'], array_column(LicenceLevel::cases(), 'value'));
    }

    public function test_labels_are_translated_in_english(): void
    {
        $this->app->setLocale('en');

        $this->assertEquals(['aci' => 'ACI Sport', 'asi' => 'ASI'], LicenceProvider::options());
        $this->assertEquals(['driver' => 'Driver', 'competitor' => 'Competitor', 'mechanic' => 'Mechanic'], LicenceRole::options());
        $this->assertEquals(['national' => 'National', 'international' => 'International'], LicenceLevel::options());
    }

    public function test_labels_are_translated_in_italian(): void
    {
        $this->app->setLocale('it');

        $this->assertEquals(['aci' => 'ACI Sport', 'asi' => 'ASI'], LicenceProvider::options());
        $this->assertEquals(['driver' => 'Conduttore', 'competitor' => 'Concorrente', 'mechanic' => 'Meccanico'], LicenceRole::options());
        $this->assertEquals(['national' => 'Nazionale', 'international' => 'Internazionale'], LicenceLevel::options());
    }

    public function test_level_maps_to_race_registration_driver_licence(): void
    {
        $this->assertEquals(DriverLicence::LOCAL_NATIONAL, LicenceLevel::National->raceLicence());
        $this->assertEquals(DriverLicence::LOCAL_INTERNATIONAL, LicenceLevel::International->raceLicence());
    }
}

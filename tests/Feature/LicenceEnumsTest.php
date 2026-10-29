<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\LicenceDocument;
use App\Enums\LicenceProvider;
use App\Enums\LicenceRole;
use Tests\TestCase;

class LicenceEnumsTest extends TestCase
{
    public function test_licence_enum_values_are_stable(): void
    {
        $this->assertEquals(['aci', 'asi'], array_column(LicenceProvider::cases(), 'value'));
        $this->assertEquals(['driver', 'mechanic'], array_column(LicenceRole::cases(), 'value'));
        $this->assertEquals(
            ['identity_document', 'medical_certificate', 'privacy', 'delegation', 'parental_authorisation'],
            array_column(LicenceDocument::cases(), 'value'),
        );
    }

    public function test_labels_are_translated_in_english(): void
    {
        $this->app->setLocale('en');

        $this->assertEquals(['aci' => 'ACI Sport', 'asi' => 'ASI'], LicenceProvider::options());
        $this->assertEquals(['driver' => 'Driver', 'mechanic' => 'Mechanic'], LicenceRole::options());
        $this->assertEquals('Medical certificate', LicenceDocument::MedicalCertificate->label());
    }

    public function test_labels_are_translated_in_italian(): void
    {
        $this->app->setLocale('it');

        $this->assertEquals(['aci' => 'ACI Sport', 'asi' => 'ASI'], LicenceProvider::options());
        $this->assertEquals(['driver' => 'Conduttore', 'mechanic' => 'Meccanico'], LicenceRole::options());
        $this->assertEquals('Certificato medico', LicenceDocument::MedicalCertificate->label());
    }
}

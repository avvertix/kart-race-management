<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Data\LicenceTypeData;
use App\Enums\LicenceDocument;
use App\Enums\LicenceDocumentKind;
use App\Enums\LicenceProvider;
use App\Enums\LicenceRole;
use App\Models\DriverLicence;
use App\Support\LicenceCatalogue;
use Tests\TestCase;

class LicenceCatalogueTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['licences.types' => [
            'aci' => [
                'naz-e' => ['name' => 'NAZ E', 'role' => LicenceRole::Driver, 'race_licence_category' => DriverLicence::LOCAL_NATIONAL],
                'naz-c' => ['name' => 'NAZ C', 'role' => LicenceRole::Driver, 'race_licence_category' => DriverLicence::LOCAL_NATIONAL, 'active' => false],
                'mechanic' => ['name' => 'Mechanic', 'role' => LicenceRole::Mechanic],
            ],
            'asi' => [
                'driver' => ['name' => 'Driver', 'role' => LicenceRole::Driver, 'race_licence_category' => DriverLicence::LOCAL_NATIONAL],
            ],
        ]]);
    }

    public function test_lists_the_active_types_of_a_provider_in_order(): void
    {
        $types = LicenceCatalogue::types(LicenceProvider::Aci);

        $this->assertEquals(['naz-e', 'mechanic'], $types->pluck('code')->all());
        $this->assertEquals('NAZ E', $types->first()->name);
        $this->assertEquals(DriverLicence::LOCAL_NATIONAL, $types->first()->raceLicenceCategory);
        $this->assertNull($types->last()->raceLicenceCategory);
    }

    public function test_finds_an_inactive_type_so_existing_licences_keep_their_name(): void
    {
        $type = LicenceCatalogue::find(LicenceProvider::Aci, 'naz-c');

        $this->assertInstanceOf(LicenceTypeData::class, $type);
        $this->assertEquals('NAZ C', $type->name);
        $this->assertFalse($type->active);
        $this->assertNull(LicenceCatalogue::find(LicenceProvider::Asi, 'naz-e'));
    }

    public function test_an_inactive_type_is_selectable_only_when_already_picked(): void
    {
        $this->assertEquals(['naz-e', 'mechanic'], LicenceCatalogue::selectableCodes(LicenceProvider::Aci));
        $this->assertEquals(['naz-e', 'naz-c', 'mechanic'], LicenceCatalogue::selectableCodes(LicenceProvider::Aci, 'naz-c'));
    }

    public function test_only_aci_driver_types_allow_the_competitor_licence(): void
    {
        $this->assertTrue(LicenceCatalogue::find(LicenceProvider::Aci, 'naz-e')->allowsCompetitor());
        $this->assertFalse(LicenceCatalogue::find(LicenceProvider::Aci, 'mechanic')->allowsCompetitor());
        $this->assertFalse(LicenceCatalogue::find(LicenceProvider::Asi, 'driver')->allowsCompetitor());
    }

    public function test_a_form_published_by_the_provider_replaces_the_pre_filled_one(): void
    {
        config(['licences.forms' => ['aci' => ['privacy' => 'https://example.com/aci-privacy.pdf']]]);

        $this->assertEquals('https://example.com/aci-privacy.pdf', LicenceCatalogue::formUrl(LicenceProvider::Aci, LicenceDocument::Privacy));
        $this->assertNull(LicenceCatalogue::formUrl(LicenceProvider::Aci, LicenceDocument::Delegation));
        $this->assertNull(LicenceCatalogue::formUrl(LicenceProvider::Asi, LicenceDocument::Privacy));
        $this->assertNull(LicenceCatalogue::formUrl(null, LicenceDocument::Privacy));
    }

    public function test_configured_provider_forms_are_secure_links_to_forms_to_sign(): void
    {
        $this->assertIsArray(config('licences.forms'));

        foreach (config('licences.forms') as $provider => $forms) {
            $this->assertNotNull(LicenceProvider::tryFrom($provider), "Unknown provider [{$provider}] in licences.forms.");

            foreach ($forms as $document => $url) {
                $this->assertSame(LicenceDocumentKind::SignedForm, LicenceDocument::tryFrom($document)?->kind(), "[{$provider}.{$document}] in licences.forms is not a form to sign.");
                $this->assertNotFalse(filter_var($url, FILTER_VALIDATE_URL), "[{$provider}.{$document}] in licences.forms is not a URL.");
                $this->assertStringStartsWith('https://', $url, "[{$provider}.{$document}] in licences.forms must use https.");
            }
        }
    }

    public function test_providers_without_types_are_not_offered(): void
    {
        config(['licences.types.asi' => []]);

        $this->assertEquals([LicenceProvider::Aci], LicenceCatalogue::providers());
    }
}

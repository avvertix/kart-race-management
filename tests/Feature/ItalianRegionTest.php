<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ItalianRegion;
use Tests\TestCase;

class ItalianRegionTest extends TestCase
{
    public function test_every_region_has_at_least_one_province(): void
    {
        foreach (ItalianRegion::cases() as $region) {
            $this->assertNotEmpty($region->provinces(), "{$region->label()} must have at least one province.");
        }
    }

    public function test_province_codes_are_unique_across_all_regions(): void
    {
        $allCodes = [];

        foreach (ItalianRegion::cases() as $region) {
            foreach ($region->provinces() as $province) {
                $this->assertArrayNotHasKey(
                    $province['code'],
                    $allCodes,
                    "Province code {$province['code']} appears in multiple regions."
                );
                $allCodes[$province['code']] = $region->value;
            }
        }
    }

    public function test_contains_province_by_code(): void
    {
        $this->assertTrue(ItalianRegion::LOMBARDIA->containsProvince('MI'));
        $this->assertTrue(ItalianRegion::LOMBARDIA->containsProvince('mi'));
        $this->assertTrue(ItalianRegion::LAZIO->containsProvince('RM'));
        $this->assertTrue(ItalianRegion::VENETO->containsProvince('VR'));
        $this->assertTrue(ItalianRegion::SICILIA->containsProvince('PA'));
    }

    public function test_contains_province_by_name(): void
    {
        $this->assertTrue(ItalianRegion::LOMBARDIA->containsProvince('Milano'));
        $this->assertTrue(ItalianRegion::LOMBARDIA->containsProvince('MILANO'));
        $this->assertTrue(ItalianRegion::LAZIO->containsProvince('Roma'));
        $this->assertTrue(ItalianRegion::VENETO->containsProvince('Verona'));
    }

    public function test_does_not_contain_foreign_province(): void
    {
        $this->assertFalse(ItalianRegion::LOMBARDIA->containsProvince('RM'));
        $this->assertFalse(ItalianRegion::LOMBARDIA->containsProvince('Roma'));
        $this->assertFalse(ItalianRegion::VENETO->containsProvince('MI'));
        $this->assertFalse(ItalianRegion::LAZIO->containsProvince('XX'));
    }

    public function test_from_province_by_code(): void
    {
        $this->assertSame(ItalianRegion::LOMBARDIA, ItalianRegion::fromProvince('MI'));
        $this->assertSame(ItalianRegion::LAZIO, ItalianRegion::fromProvince('RM'));
        $this->assertSame(ItalianRegion::SICILIA, ItalianRegion::fromProvince('CT'));
        $this->assertSame(ItalianRegion::PIEMONTE, ItalianRegion::fromProvince('TO'));
    }

    public function test_from_province_by_name(): void
    {
        $this->assertSame(ItalianRegion::LOMBARDIA, ItalianRegion::fromProvince('Milano'));
        $this->assertSame(ItalianRegion::LAZIO, ItalianRegion::fromProvince('Roma'));
    }

    public function test_from_province_returns_null_for_unknown(): void
    {
        $this->assertNull(ItalianRegion::fromProvince('XX'));
        $this->assertNull(ItalianRegion::fromProvince(''));
        $this->assertNull(ItalianRegion::fromProvince('Unknown Province'));
    }

    public function test_labels_are_non_empty(): void
    {
        foreach (ItalianRegion::cases() as $region) {
            $this->assertNotEmpty($region->label());
        }
    }
}

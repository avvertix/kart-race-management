<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\LicenceFormTemplate;
use App\Enums\LicenceLevel;
use App\Enums\LicenceRole;
use App\Models\LicenceType;
use Database\Seeders\LicenceTypeSeeder;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class LicenceTypeSeederTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_seeds_aci_driver_competitor_and_mechanic_types_with_a_self_declaration(): void
    {
        $this->seed(LicenceTypeSeeder::class);

        $licenceTypes = LicenceType::with(['grades', 'documents'])->get();

        $this->assertEquals(
            [LicenceRole::Driver, LicenceRole::Competitor, LicenceRole::Mechanic],
            $licenceTypes->pluck('role')->all(),
        );

        foreach ($licenceTypes as $licenceType) {
            $this->assertTrue(
                $licenceType->documents->contains(fn ($document) => $document->template === LicenceFormTemplate::SelfDeclaration && $document->required),
                "{$licenceType->name} has no required self-declaration",
            );
        }

        $driverGrades = $licenceTypes->first()->grades;

        $this->assertTrue($driverGrades->contains('level', LicenceLevel::National));
        $this->assertTrue($driverGrades->contains('level', LicenceLevel::International));
    }

    public function test_seeding_twice_keeps_existing_types(): void
    {
        $this->seed(LicenceTypeSeeder::class);
        $this->seed(LicenceTypeSeeder::class);

        $this->assertEquals(3, LicenceType::count());
        $this->assertEquals(6, LicenceType::where('role', LicenceRole::Driver)->sole()->grades()->count());
    }
}

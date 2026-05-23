<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\RegisterParticipant;
use App\Enums\ItalianRegion;
use App\Models\Category;
use App\Models\ItalianPostalCode;
use App\Models\Race;
use App\Models\RaceType;
use Illuminate\Support\Facades\Notification;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\CreateCompetitor;
use Tests\CreateDriver;
use Tests\CreateMechanic;
use Tests\CreateVehicle;
use Tests\TestCase;

class DetermineParticipantZoneTest extends TestCase
{
    use CreateCompetitor;
    use CreateDriver;
    use CreateMechanic;
    use CreateVehicle;
    use FastRefreshDatabase;

    public function test_region_is_detected_from_province_code_at_registration(): void
    {
        config(['races.registration.form' => 'complete']);
        Notification::fake();

        $race = Race::factory()->create(['type' => RaceType::NATIONAL->value]);
        $category = Category::factory()->recycle($race->championship)->create();

        $this->travelTo($race->registration_closes_at->subHour());

        $participant = app(RegisterParticipant::class)($race, [
            'bib' => 1,
            'category' => $category->ulid,
            ...$this->generateValidDriver(['driver_residence_province' => '']),
            'driver_residence_province' => 'MI',
            ...$this->generateValidVehicle(),
            'consent_privacy' => true,
        ]);

        $this->travelBack();

        $this->assertSame(ItalianRegion::LOMBARDIA, $participant->fresh()->region);
    }

    public function test_region_is_detected_from_province_name_at_registration(): void
    {
        config(['races.registration.form' => 'complete']);
        Notification::fake();

        $race = Race::factory()->create(['type' => RaceType::NATIONAL->value]);
        $category = Category::factory()->recycle($race->championship)->create();

        $this->travelTo($race->registration_closes_at->subHour());

        $participant = app(RegisterParticipant::class)($race, [
            'bib' => 1,
            'category' => $category->ulid,
            ...$this->generateValidDriver(['driver_residence_province' => '']),
            'driver_residence_province' => 'Milano',
            ...$this->generateValidVehicle(),
            'consent_privacy' => true,
        ]);

        $this->travelBack();

        $this->assertSame(ItalianRegion::LOMBARDIA, $participant->fresh()->region);
    }

    public function test_region_is_null_for_unknown_province_at_registration(): void
    {
        config(['races.registration.form' => 'complete']);
        Notification::fake();

        $race = Race::factory()->create(['type' => RaceType::NATIONAL->value]);
        $category = Category::factory()->recycle($race->championship)->create();

        $this->travelTo($race->registration_closes_at->subHour());

        $participant = app(RegisterParticipant::class)($race, [
            'bib' => 1,
            'category' => $category->ulid,
            ...$this->generateValidDriver(['driver_residence_province' => '']),
            'driver_residence_province' => 'XX',
            ...$this->generateValidVehicle(),
            'consent_privacy' => true,
        ]);

        $this->travelBack();

        $this->assertNull($participant->fresh()->region);
    }

    public function test_out_of_zone_auto_set_when_race_has_zone_configured_and_province_known(): void
    {
        config(['races.registration.form' => 'complete']);
        Notification::fake();

        $race = Race::factory()->create([
            'type' => RaceType::NATIONAL->value,
            'zone_regions' => [ItalianRegion::LOMBARDIA->value],
        ]);
        $category = Category::factory()->recycle($race->championship)->create();

        $this->travelTo($race->registration_closes_at->subHour());

        $inZoneParticipant = app(RegisterParticipant::class)($race, [
            'bib' => 1,
            'category' => $category->ulid,
            ...$this->generateValidDriver(['driver_residence_province' => '']),
            'driver_residence_province' => 'MI',
            ...$this->generateValidVehicle(),
            'consent_privacy' => true,
        ]);

        $outOfZoneParticipant = app(RegisterParticipant::class)($race, [
            'bib' => 2,
            'category' => $category->ulid,
            ...$this->generateValidDriver(['driver_residence_province' => '', 'driver_licence_number' => '']),
            'driver_licence_number' => 'D9999',
            'driver_residence_province' => 'RM',
            ...$this->generateValidVehicle(),
            'consent_privacy' => true,
        ]);

        $this->travelBack();

        $this->assertFalse($inZoneParticipant->fresh()->properties['out_of_zone']);
        $this->assertTrue($outOfZoneParticipant->fresh()->properties['out_of_zone']);
    }

    public function test_out_of_zone_not_set_when_local_race(): void
    {
        config(['races.registration.form' => 'complete']);
        Notification::fake();

        $race = Race::factory()->create([
            'type' => RaceType::LOCAL->value,
            'zone_regions' => null,
        ]);
        $category = Category::factory()->recycle($race->championship)->create();

        $this->travelTo($race->registration_closes_at->subHour());

        $participant = app(RegisterParticipant::class)($race, [
            'bib' => 1,
            'category' => $category->ulid,
            ...$this->generateValidDriver(['driver_residence_province' => '']),
            'driver_residence_province' => 'MI',
            ...$this->generateValidVehicle(),
            'consent_privacy' => true,
        ]);

        $this->travelBack();

        $this->assertNull($participant->fresh()->properties['out_of_zone'] ?? null);
    }

    public function test_out_of_zone_not_set_when_race_has_no_zone_configured(): void
    {
        config(['races.registration.form' => 'complete']);
        Notification::fake();

        $race = Race::factory()->create([
            'type' => RaceType::NATIONAL->value,
            'zone_regions' => null,
        ]);
        $category = Category::factory()->recycle($race->championship)->create();

        $this->travelTo($race->registration_closes_at->subHour());

        $participant = app(RegisterParticipant::class)($race, [
            'bib' => 1,
            'category' => $category->ulid,
            ...$this->generateValidDriver(['driver_residence_province' => '']),
            'driver_residence_province' => 'MI',
            ...$this->generateValidVehicle(),
            'consent_privacy' => true,
        ]);

        $this->travelBack();

        $this->assertNull($participant->fresh()->properties['out_of_zone'] ?? null);
    }

    public function test_out_of_zone_defaults_to_true_when_region_cannot_be_determined(): void
    {
        config(['races.registration.form' => 'complete']);
        Notification::fake();

        $race = Race::factory()->create([
            'type' => RaceType::NATIONAL->value,
            'zone_regions' => [ItalianRegion::LOMBARDIA->value],
        ]);
        $category = Category::factory()->recycle($race->championship)->create();

        $this->travelTo($race->registration_closes_at->subHour());

        // Unknown province + unknown CAP → cannot determine region → defaults to out-of-zone
        $participant = app(RegisterParticipant::class)($race, [
            'bib' => 1,
            'category' => $category->ulid,
            ...$this->generateValidDriver(['driver_residence_province' => '']),
            'driver_residence_province' => 'XX',
            'driver_residence_postal_code' => '99999',
            ...$this->generateValidVehicle(),
            'consent_privacy' => true,
        ]);

        $this->travelBack();

        $this->assertTrue($participant->fresh()->properties['out_of_zone']);
    }

    public function test_region_detected_via_cap_when_province_is_blank(): void
    {
        config(['races.registration.form' => 'complete']);
        Notification::fake();

        ItalianPostalCode::create([
            'cap' => '20100',
            'province_code' => 'MI',
            'province' => 'Milano',
            'municipality' => 'Milano',
            'region' => ItalianRegion::LOMBARDIA->value,
        ]);

        $race = Race::factory()->create([
            'type' => RaceType::NATIONAL->value,
            'zone_regions' => [ItalianRegion::LOMBARDIA->value],
        ]);
        $category = Category::factory()->recycle($race->championship)->create();

        $this->travelTo($race->registration_closes_at->subHour());

        $participant = app(RegisterParticipant::class)($race, [
            'bib' => 1,
            'category' => $category->ulid,
            ...$this->generateValidDriver(['driver_residence_province' => '']),
            'driver_residence_province' => '',
            'driver_residence_postal_code' => '20100',
            ...$this->generateValidVehicle(),
            'consent_privacy' => true,
        ]);

        $this->travelBack();

        $fresh = $participant->fresh();
        $this->assertSame(ItalianRegion::LOMBARDIA, $fresh->region);
        $this->assertFalse($fresh->properties['out_of_zone']);
    }

    public function test_cap_fallback_marks_out_of_zone_when_region_not_in_zone(): void
    {
        config(['races.registration.form' => 'complete']);
        Notification::fake();

        ItalianPostalCode::create([
            'cap' => '00100',
            'province_code' => 'RM',
            'province' => 'Roma',
            'municipality' => 'Roma',
            'region' => ItalianRegion::LAZIO->value,
        ]);

        $race = Race::factory()->create([
            'type' => RaceType::NATIONAL->value,
            'zone_regions' => [ItalianRegion::LOMBARDIA->value],
        ]);
        $category = Category::factory()->recycle($race->championship)->create();

        $this->travelTo($race->registration_closes_at->subHour());

        $participant = app(RegisterParticipant::class)($race, [
            'bib' => 1,
            'category' => $category->ulid,
            ...$this->generateValidDriver(['driver_residence_province' => '']),
            'driver_residence_province' => '',
            'driver_residence_postal_code' => '00100',
            ...$this->generateValidVehicle(),
            'consent_privacy' => true,
        ]);

        $this->travelBack();

        $fresh = $participant->fresh();
        $this->assertSame(ItalianRegion::LAZIO, $fresh->region);
        $this->assertTrue($fresh->properties['out_of_zone']);
    }
}

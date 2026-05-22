<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ItalianRegion;
use App\Models\Participant;
use App\Models\Race;
use App\Models\RaceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutOfZoneDetectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_null_when_race_has_no_zone_configured(): void
    {
        $race = Race::factory()->create(['type' => RaceType::NATIONAL->value, 'zone_regions' => null]);

        $participant = $this->makeParticipant($race, 'MI');

        $this->assertNull($participant->detectOutOfZone($race));
    }

    public function test_returns_null_when_participant_has_no_province(): void
    {
        $race = Race::factory()->create([
            'type' => RaceType::NATIONAL->value,
            'zone_regions' => [ItalianRegion::LOMBARDIA->value],
        ]);

        $participant = $this->makeParticipant($race, '');

        $this->assertNull($participant->detectOutOfZone($race));
    }

    public function test_detects_within_zone_by_province_code(): void
    {
        $race = Race::factory()->create([
            'type' => RaceType::NATIONAL->value,
            'zone_regions' => [ItalianRegion::LOMBARDIA->value],
        ]);

        $participant = $this->makeParticipant($race, 'MI');

        $this->assertFalse($participant->detectOutOfZone($race));
    }

    public function test_detects_out_of_zone_by_province_code(): void
    {
        $race = Race::factory()->create([
            'type' => RaceType::NATIONAL->value,
            'zone_regions' => [ItalianRegion::LOMBARDIA->value],
        ]);

        $participant = $this->makeParticipant($race, 'RM');

        $this->assertTrue($participant->detectOutOfZone($race));
    }

    public function test_detects_within_zone_by_province_name(): void
    {
        $race = Race::factory()->create([
            'type' => RaceType::NATIONAL->value,
            'zone_regions' => [ItalianRegion::LOMBARDIA->value],
        ]);

        $participant = $this->makeParticipant($race, 'Milano');

        $this->assertFalse($participant->detectOutOfZone($race));
    }

    public function test_detects_out_of_zone_by_province_name(): void
    {
        $race = Race::factory()->create([
            'type' => RaceType::NATIONAL->value,
            'zone_regions' => [ItalianRegion::LOMBARDIA->value],
        ]);

        $participant = $this->makeParticipant($race, 'Roma');

        $this->assertTrue($participant->detectOutOfZone($race));
    }

    public function test_within_zone_when_province_belongs_to_any_configured_region(): void
    {
        $race = Race::factory()->create([
            'type' => RaceType::NATIONAL->value,
            'zone_regions' => [ItalianRegion::LOMBARDIA->value, ItalianRegion::PIEMONTE->value],
        ]);

        $participant = $this->makeParticipant($race, 'TO'); // Torino = Piemonte

        $this->assertFalse($participant->detectOutOfZone($race));
    }

    public function test_region_property_returns_correct_region(): void
    {
        $race = Race::factory()->create(['type' => RaceType::NATIONAL->value]);

        $participant = $this->makeParticipant($race, 'MI');

        $this->assertSame(ItalianRegion::LOMBARDIA, $participant->region);
    }

    public function test_region_property_returns_null_for_unknown_province(): void
    {
        $race = Race::factory()->create(['type' => RaceType::NATIONAL->value]);

        $participant = $this->makeParticipant($race, 'XX');

        $this->assertNull($participant->region);
    }

    public function test_region_property_returns_null_when_no_province(): void
    {
        $race = Race::factory()->create(['type' => RaceType::NATIONAL->value]);

        $participant = $this->makeParticipant($race, '');

        $this->assertNull($participant->region);
    }

    public function test_race_has_zone_configured(): void
    {
        $race = Race::factory()->create([
            'zone_regions' => [ItalianRegion::LOMBARDIA->value],
        ]);

        $this->assertTrue($race->hasZoneConfigured());
    }

    public function test_race_has_no_zone_configured(): void
    {
        $race = Race::factory()->create(['zone_regions' => null]);

        $this->assertFalse($race->hasZoneConfigured());
    }

    public function test_is_province_in_zone(): void
    {
        $race = Race::factory()->create([
            'zone_regions' => [ItalianRegion::LOMBARDIA->value, ItalianRegion::PIEMONTE->value],
        ]);

        $this->assertTrue($race->isProvinceInZone('MI'));
        $this->assertTrue($race->isProvinceInZone('TO'));
        $this->assertFalse($race->isProvinceInZone('RM'));
    }

    public function test_race_zone_regions_saved_via_controller(): void
    {
        $user = User::factory()->admin()->create();

        $race = Race::factory()->create(['type' => RaceType::NATIONAL->value]);

        $this->travelTo('2023-03-01');

        $response = $this->actingAs($user)->put(route('races.update', $race), $this->raceUpdatePayload($race, [
            'race_type' => RaceType::NATIONAL->value,
            'zone_regions' => [ItalianRegion::LOMBARDIA->value, ItalianRegion::VENETO->value],
        ]));

        $this->travelBack();

        $response->assertRedirect();

        $race->refresh();

        $this->assertTrue($race->hasZoneConfigured());
        $this->assertContains(ItalianRegion::LOMBARDIA->value, $race->zone_regions->toArray());
        $this->assertContains(ItalianRegion::VENETO->value, $race->zone_regions->toArray());
    }

    public function test_race_zone_regions_can_be_cleared(): void
    {
        $user = User::factory()->admin()->create();

        $race = Race::factory()->create([
            'type' => RaceType::NATIONAL->value,
            'zone_regions' => [ItalianRegion::LOMBARDIA->value],
        ]);

        $this->travelTo('2023-03-01');

        $this->actingAs($user)->put(route('races.update', $race), $this->raceUpdatePayload($race, [
            'race_type' => RaceType::NATIONAL->value,
            // no zone_regions — should clear
        ]));

        $this->travelBack();

        $race->refresh();

        $this->assertFalse($race->hasZoneConfigured());
    }

    private function makeParticipant(Race $race, string $province): Participant
    {
        $participant = Participant::factory()
            ->recycle($race->championship)
            ->category()
            ->create(['race_id' => $race->getKey()]);

        $driver = $participant->driver;
        $driver['residence_address']['province'] = $province;
        $participant->driver = $driver;
        $participant->region = filled($province) ? ItalianRegion::fromProvince($province) : null;
        $participant->save();

        return $participant;
    }

    /** Build a minimal valid race update payload from an existing race. */
    private function raceUpdatePayload(Race $race, array $overrides = []): array
    {
        return array_merge([
            'start' => '2023-03-10',
            'end' => '2023-03-10',
            'title' => $race->title,
            'description' => '',
            'track' => $race->track,
            'participants_total_limit' => '',
            'race_type' => $race->type?->value ?? RaceType::LOCAL->value,
            'hidden' => 'false',
        ], $overrides);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Operations;

use App\Models\Championship;
use App\Models\Race;
use App\Models\RaceType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class CompactRaceTypesTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_regional_race_type_is_converted_to_national()
    {
        $uuid = $this->createRaceWithRawType(20); // REGIONAL (deprecated)

        $this->artisan('operations:process 2025_12_31_105056_compact_race_types')
            ->assertSuccessful();

        $updatedRace = Race::where('uuid', $uuid)->first();

        $this->assertEquals(RaceType::NATIONAL, $updatedRace->type);
        $this->assertEquals(40, $updatedRace->type->value);
    }

    public function test_zone_race_type_is_converted_to_national()
    {
        $uuid = $this->createRaceWithRawType(30); // ZONE (deprecated)

        $this->artisan('operations:process 2025_12_31_105056_compact_race_types')
            ->assertSuccessful();

        $updatedRace = Race::where('uuid', $uuid)->first();

        $this->assertEquals(RaceType::NATIONAL, $updatedRace->type);
        $this->assertEquals(40, $updatedRace->type->value);
    }

    public function test_local_race_type_remains_unchanged()
    {
        $race = Race::factory()->create([
            'type' => RaceType::LOCAL,
        ]);

        $this->artisan('operations:process 2025_12_31_105056_compact_race_types')
            ->assertSuccessful();

        $updatedRace = $race->fresh();

        $this->assertEquals(RaceType::LOCAL, $updatedRace->type);
        $this->assertEquals(10, $updatedRace->type->value);
    }

    public function test_national_race_type_remains_unchanged()
    {
        $race = Race::factory()->create([
            'type' => RaceType::NATIONAL,
        ]);

        $this->artisan('operations:process 2025_12_31_105056_compact_race_types')
            ->assertSuccessful();

        $updatedRace = $race->fresh();

        $this->assertEquals(RaceType::NATIONAL, $updatedRace->type);
        $this->assertEquals(40, $updatedRace->type->value);
    }

    public function test_international_race_type_remains_unchanged()
    {
        $race = Race::factory()->create([
            'type' => RaceType::INTERNATIONAL,
        ]);

        $this->artisan('operations:process 2025_12_31_105056_compact_race_types')
            ->assertSuccessful();

        $updatedRace = $race->fresh();

        $this->assertEquals(RaceType::INTERNATIONAL, $updatedRace->type);
        $this->assertEquals(50, $updatedRace->type->value);
    }

    public function test_multiple_races_with_different_types_are_processed_correctly()
    {
        // Create races with deprecated types using raw inserts
        $regionalUuid = $this->createRaceWithRawType(20); // REGIONAL
        $zoneUuid = $this->createRaceWithRawType(30); // ZONE

        // Create races with valid types
        $localRace = Race::factory()->create(['type' => RaceType::LOCAL]);
        $nationalRace = Race::factory()->create(['type' => RaceType::NATIONAL]);
        $internationalRace = Race::factory()->create(['type' => RaceType::INTERNATIONAL]);

        $this->artisan('operations:process 2025_12_31_105056_compact_race_types')
            ->assertSuccessful();

        // Assert deprecated types are converted to NATIONAL
        $this->assertEquals(RaceType::NATIONAL, Race::where('uuid', $regionalUuid)->first()->type);
        $this->assertEquals(RaceType::NATIONAL, Race::where('uuid', $zoneUuid)->first()->type);

        // Assert existing types remain unchanged
        $this->assertEquals(RaceType::LOCAL, $localRace->fresh()->type);
        $this->assertEquals(RaceType::NATIONAL, $nationalRace->fresh()->type);
        $this->assertEquals(RaceType::INTERNATIONAL, $internationalRace->fresh()->type);
    }

    public function test_operation_handles_no_deprecated_race_types()
    {
        // Create only valid race types
        Race::factory()->create(['type' => RaceType::LOCAL]);
        Race::factory()->create(['type' => RaceType::NATIONAL]);
        Race::factory()->create(['type' => RaceType::INTERNATIONAL]);

        $this->artisan('operations:process 2025_12_31_105056_compact_race_types')
            ->assertSuccessful();

        $races = Race::all();

        // All races should maintain their original types
        $this->assertEquals(RaceType::LOCAL, $races[0]->type);
        $this->assertEquals(RaceType::NATIONAL, $races[1]->type);
        $this->assertEquals(RaceType::INTERNATIONAL, $races[2]->type);
    }

    /**
     * Create a race with a raw type value to bypass enum validation.
     */
    private function createRaceWithRawType(int $typeValue): string
    {
        $championship = Championship::factory()->create();
        $race = Race::factory()->make([
            'championship_id' => $championship->id,
        ]);

        // Manually insert to bypass enum casting
        $uuid = (string) Str::ulid();
        DB::table('races')->insert([
            'uuid' => $uuid,
            'event_start_at' => $race->event_start_at,
            'event_end_at' => $race->event_end_at,
            'registration_opens_at' => $race->registration_opens_at,
            'registration_closes_at' => $race->registration_closes_at,
            'track' => $race->track,
            'title' => $race->title,
            'description' => $race->description,
            'tags' => json_encode($race->tags ?? []),
            'properties' => json_encode($race->properties ?? []),
            'hide' => $race->hide ?? false,
            'participant_limits' => json_encode($race->participant_limits ?? null),
            'championship_id' => $championship->id,
            'type' => $typeValue,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $uuid;
    }
}

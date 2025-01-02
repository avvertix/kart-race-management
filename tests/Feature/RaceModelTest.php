<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\Race;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RaceModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_open_races_can_be_retrieved()
    {
        $this->createRaces();

        $this->travelTo(Carbon::parse('2022-12-29 10:00'));

        $expectedRaces = Race::query()->withRegistrationOpen()->get();

        $this->assertEquals(1, $expectedRaces->count());

        $nextRace = $expectedRaces->first();

        $this->assertEquals('registration_open', $nextRace->status);
        $this->assertTrue($nextRace->isRegistrationOpen);
        $this->assertEquals('2022-12-30 00:00:00', $nextRace->event_start_at->toDateTimeString());

        $this->travelBack();
    }

    public function test_active_races_can_be_retrieved()
    {
        $this->createRaces();

        $this->travelTo(Carbon::parse('2022-12-30 10:00'));

        $expectedRaces = Race::query()->active()->get();

        $this->assertEquals(1, $expectedRaces->count());

        $nextRace = $expectedRaces->first();

        $this->assertEquals('active', $nextRace->status);
        $this->assertFalse($nextRace->isRegistrationOpen);
        $this->assertEquals('2022-12-30 00:00:00', $nextRace->event_start_at->toDateTimeString());

        $this->travelBack();
    }

    public function test_race_status_returned()
    {
        $this->createRaces();

        $this->travelTo(Carbon::parse('2022-12-29 10:00'));

        $expectedRaces = Race::all();

        $this->assertEquals(3, $expectedRaces->count());

        $statuses = $expectedRaces->map->status;

        $this->assertEquals(['concluded', 'registration_open', 'scheduled'], $statuses->toArray());

        $this->travelBack();
    }

    protected function createRaces()
    {
        $championship = Championship::factory()->create();

        $races = Race::factory()
            ->count(3)
            ->state(new Sequence(
                [
                    'event_start_at' => Carbon::parse('2022-12-28 09:00'), 'event_end_at' => Carbon::parse('2022-12-28 18:00'),
                    'registration_opens_at' => Carbon::parse('2022-12-26 09:00'), 'registration_closes_at' => Carbon::parse('2022-12-28 08:00'),
                ],
                [
                    'event_start_at' => Carbon::parse('2022-12-30 00:00'), 'event_end_at' => Carbon::parse('2022-12-30 23:59'),
                    'registration_opens_at' => Carbon::parse('2022-12-29 09:00'), 'registration_closes_at' => Carbon::parse('2022-12-29 23:00'),
                ],
                [
                    'event_start_at' => Carbon::parse('2023-01-22 09:00'), 'event_end_at' => Carbon::parse('2023-01-22 18:00'),
                    'registration_opens_at' => Carbon::parse('2023-01-15 09:00'), 'registration_closes_at' => Carbon::parse('2023-01-22 08:00'),
                ],
            ))
            ->for($championship)
            ->create();

        return $races;
    }
}

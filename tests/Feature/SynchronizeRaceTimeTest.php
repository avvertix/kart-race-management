<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Race;
use Carbon\Carbon;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class SynchronizeRaceTimeTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_race_time_synchronized()
    {
        config([
            'races.start_time' => '09:00:00',
            'races.end_time' => '18:00:00',
            'races.timezone' => 'Europe/Rome',
        ]);

        $this->travelTo(Carbon::parse('2023-03-05'));

        $raceDate = Carbon::parse('2023-03-05');

        $race = Race::factory()->create([
            'event_start_at' => $raceDate,
            'event_end_at' => $raceDate,
        ]);

        $this->artisan('races:sync-time')
            ->assertSuccessful();

        $this->travelBack();

        $updatedRace = $race->fresh();

        $this->assertEquals(Carbon::parse('2023-03-05 09:00:00', config('races.timezone'))->setTimezone(config('app.timezone'))->toDateTimeString(), $updatedRace->event_start_at->toDateTimeString());
        $this->assertEquals(Carbon::parse('2023-03-05 18:00:00', config('races.timezone'))->setTimezone(config('app.timezone'))->toDateTimeString(), $updatedRace->event_end_at->toDateTimeString());
        $this->assertEquals(Carbon::parse('2023-02-26 09:00:00', config('races.timezone'))->setTimezone(config('app.timezone'))->toDateTimeString(), $updatedRace->registration_opens_at->toDateTimeString());
        $this->assertEquals(Carbon::parse('2023-03-05 08:00:00', config('races.timezone'))->setTimezone(config('app.timezone'))->toDateTimeString(), $updatedRace->registration_closes_at->toDateTimeString());
    }
}

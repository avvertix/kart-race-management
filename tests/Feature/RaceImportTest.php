<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\Race;
use App\Models\User;
use Carbon\Carbon;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class RaceImportTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_can_import_races()
    {
        $user = User::factory()->organizer()->create();

        $championship = Championship::factory()->create();

        $this->travelTo(Carbon::parse('2023-03-04'));

        $response = $this
            ->actingAs($user)
            ->from(route('championships.races.import.create', $championship))
            ->post(route('championships.races.import.store', $championship), [
                'races' => '2023-03-05;2023-03-05;Race title;Track;Description of the race;'.PHP_EOL.'2023-03-07;2023-03-07;Second Race;Track;Description of the race',
            ]);

        $this->travelBack();

        $response->assertRedirectToRoute('championships.show', $championship);

        $response->assertSessionHasNoErrors();

        $response->assertSessionHas('flash.banner', 'Races imported.');

        $this->assertEquals(2, Race::count());

        $race = Race::first();

        $this->assertInstanceOf(Race::class, $race);
        $this->assertEquals('Race title', $race->title);
        $this->assertEquals('Description of the race', $race->description);
        $this->assertEquals('Track', $race->track);
        $this->assertTrue($race->championship->is($championship));
        $this->assertEquals('2023-03-05 09:00:00', $race->event_start_at->toDateTimeString());
        $this->assertEquals('2023-03-05 18:00:00', $race->event_end_at->toDateTimeString());
        $this->assertEquals('2023-02-26 09:00:00', $race->registration_opens_at->toDateTimeString());
        $this->assertEquals('2023-03-05 08:00:00', $race->registration_closes_at->toDateTimeString());
    }

    public function test_invalid_race_cannot_be_imported()
    {
        $user = User::factory()->organizer()->create();

        $championship = Championship::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('championships.races.import.create', $championship))
            ->post(route('championships.races.import.store', $championship), [
                'races' => '2023-03-05;2023-03-05;;Track;Description of the race;'.PHP_EOL.'2023-03-07;2023-03-07;Second Race;Track;Description of the race',
            ]);

        $response->assertRedirectToRoute('championships.races.import.create', $championship);

        $response->assertSessionHasErrors();

        $this->assertEquals(0, Race::count());

    }
}

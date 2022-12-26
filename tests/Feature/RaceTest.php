<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\Race;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Collection;
use Tests\TestCase;

class RaceTest extends TestCase
{
    use RefreshDatabase;


    public function test_races_screen_can_be_rendered()
    {
        $user = User::factory()->organizer()->create();

        $race = Race::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('championships.races.index', ['championship' => $race->championship]));

        $response->assertOk();

        $races = $response->viewData('races');

        $this->assertInstanceOf(Collection::class, $races);
        $this->assertTrue($races->first()->is($race));
        $this->assertTrue($response->viewData('championship')->is($race->championship));
        
    }

    public function test_new_race_can_be_created()
    {
        $user = User::factory()->organizer()->create();

        $championship = Championship::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('championships.races.create', $championship))
            ->post(route('championships.races.store', $championship), [
                'start' => '2023-03-05',
                'end' => '2023-03-05',
                'track' => 'Franciacorta',
                'title' => 'First Race',
                'description' => 'a little description',
            ]);

        $response->assertRedirectToRoute('championships.races.index', ['championship' => $championship]);
        
        $response->assertSessionHasNoErrors();

        $response->assertSessionHas('message', 'First Race created.');

        $race = Race::first();

        $this->assertInstanceOf(Race::class, $race);
        $this->assertEquals('First Race', $race->title);
        $this->assertEquals('a little description', $race->description);
        $this->assertEquals('Franciacorta', $race->track);
        $this->assertTrue($race->championship->is($championship));
        $this->assertEquals(Carbon::parse('2023-03-05'), $race->event_start_at);
        $this->assertEquals(Carbon::parse('2023-03-05'), $race->event_end_at);
    }

}

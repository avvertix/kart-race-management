<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\Race;
use App\Models\Tire;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RaceTiresControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authentication_required()
    {
        $race = Race::factory()->create();

        $response = $this->get(route('races.tires', $race));

        $response->assertRedirectToRoute('login');
    }

    public function test_timekeeper_cannot_access()
    {
        $user = User::factory()->timekeeper()->create();

        $race = Race::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('races.tires', $race));

        $response->assertForbidden();
    }

    public function test_assigned_tires_are_listed()
    {
        $user = User::factory()->tireagent()->create();

        $race = Race::factory()->create();

        $otherRace = Race::factory()->create([
            'championship_id' => $race->championship->getKey(),
        ]);

        $participant = Participant::factory()
            ->has(Tire::factory()->count(2)->state([
                'race_id' => $race->getKey(),
            ]))
            ->create([
                'race_id' => $race->getKey(),
                'championship_id' => $race->championship->getKey(),
            ]);

        $otherParticipant = Participant::factory()
            ->has(Tire::factory()->count(2)->state([
                'race_id' => $otherRace->getKey(),
            ]))
            ->create([
                'race_id' => $otherRace->getKey(),
                'championship_id' => $otherRace->championship->getKey(),
            ]);

        $response = $this
            ->actingAs($user)
            ->get(route('races.tires', $race));

        $response->assertSuccessful();

        $response->assertViewHas('race', $race);

        $response->assertViewHas('championship', $race->championship);

        $response->assertViewHas('participants');

        $participants = $response->viewData('participants');

        $this->assertTrue($participants->first()->is($participant));
        $this->assertEquals(1, $participants->count());
    }

    public function test_search_is_limited_to_tires_assigned_to_current_race()
    {
        $user = User::factory()->tireagent()->create();

        $race = Race::factory()->create();

        $otherRace = Race::factory()->create([
            'championship_id' => $race->championship->getKey(),
        ]);

        $participant = Participant::factory()
            ->has(Tire::factory()->state([
                'race_id' => $race->getKey(),
                'code' => '1',
            ]))
            ->create([
                'race_id' => $race->getKey(),
                'championship_id' => $race->championship->getKey(),
            ]);

        $otherParticipant = Participant::factory()
            ->has(Tire::factory()->state([
                'race_id' => $otherRace->getKey(),
                'code' => '1',
            ]))
            ->create([
                'race_id' => $otherRace->getKey(),
                'championship_id' => $otherRace->championship->getKey(),
            ]);

        $response = $this
            ->actingAs($user)
            ->get(route('races.tires', ['race' => $race, 'tire_search' => '1']));

        $response->assertSuccessful();

        $response->assertViewHas('race', $race);

        $response->assertViewHas('championship', $race->championship);

        $response->assertViewHas('participants');

        $participants = $response->viewData('participants');

        $this->assertTrue($participants->first()->is($participant));
        $this->assertEquals(1, $participants->count());
    }
}

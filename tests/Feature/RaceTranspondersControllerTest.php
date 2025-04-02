<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\Race;
use App\Models\Transponder;
use App\Models\User;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class RaceTranspondersControllerTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_authentication_required()
    {
        $race = Race::factory()->create();

        $response = $this->get(route('races.transponders', $race));

        $response->assertRedirectToRoute('login');
    }

    public function test_tiremanager_cannot_access()
    {
        $user = User::factory()->tireagent()->create();

        $race = Race::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('races.transponders', $race));

        $response->assertForbidden();
    }

    public function test_assigned_transponders_are_listed()
    {
        $user = User::factory()->timekeeper()->create();

        $race = Race::factory()->create();

        $participant = Participant::factory()
            ->has(Transponder::factory()->state([
                'race_id' => $race->getKey(),
            ]))
            ->create([
                'race_id' => $race->getKey(),
                'championship_id' => $race->championship->getKey(),
            ]);

        $response = $this
            ->actingAs($user)
            ->get(route('races.transponders', $race));

        $response->assertSuccessful();

        $response->assertViewHas('race', $race);

        $response->assertViewHas('championship', $race->championship);

        $response->assertViewHas('participants');

        $participants = $response->viewData('participants');

        $this->assertTrue($participants->first()->is($participant));
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\Participant;
use App\Models\Race;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChampionshipParticipantControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_listing_championship_participants_requires_login()
    {
        $championship = Championship::factory()->create();

        $response = $this->get(route('championships.participants.index', $championship));

        $response->assertRedirectToRoute('login');
    }

    public function test_listing_championship_participants_shows_participations()
    {
        $user = User::factory()->admin()->create();

        $championship = Championship::factory()
            ->has(Race::factory()->count(2))
            ->create();

        $firstRace = $championship->races->first();
        $lastRace = $championship->races->last();

        $participationToFirstRace = Participant::factory()
            ->for($firstRace)
            ->for($championship)
            ->driver([
                'first_name' => 'John',
                'last_name' => 'Racer',
                'licence_number' => 'LN1',
                'bib' => 100,
            ])
            ->create();

        $participationToLastRace = Participant::factory()
            ->for($lastRace)
            ->for($championship)
            ->driver([
                'first_name' => 'John',
                'last_name' => 'Racer',
                'licence_number' => 'LN1',
                'bib' => 101,
            ])
            ->create();

        $response = $this
            ->actingAs($user)
            ->get(route('championships.participants.index', $championship));

        $response->assertSuccessful();

        $response->assertViewHas('championship', $championship);

        $response->assertViewHas('uniqueParticipantsCount', 1);

        $response->assertViewHas('participants');

        $participant = $response->viewData('participants')->first();

        $this->assertEquals(101, $participant->bib);
        $this->assertEquals('John', $participant->first_name);
        $this->assertEquals('Racer', $participant->last_name);

        $this->assertTrue($participant->participationHistory->first()->is($participationToFirstRace));
        $this->assertTrue($participant->participationHistory->last()->is($participationToLastRace));

        $response->assertSee('John Racer');
        $response->assertSeeInOrder([
            'border border-red-500 bg-red-50',
            $firstRace->title,
            '100',
        ]);

    }

    public function test_only_current_championship_participations_listed()
    {
        $user = User::factory()->admin()->create();

        $championship = Championship::factory()
            ->has(Race::factory()->count(2))
            ->create();

        $firstRace = $championship->races->first();
        $lastRace = $championship->races->last();

        $raceInDifferentChampionship = Race::factory()->create();

        $participationToFirstRace = Participant::factory()
            ->for($firstRace)
            ->for($championship)
            ->driver([
                'first_name' => 'John',
                'last_name' => 'Racer',
                'licence_number' => 'LN1',
                'bib' => 100,
            ])
            ->create();

        $participationToLastRace = Participant::factory()
            ->for($lastRace)
            ->for($championship)
            ->driver([
                'first_name' => 'John',
                'last_name' => 'Racer',
                'licence_number' => 'LN1',
                'bib' => 101,
            ])
            ->create();

        $participationToOtherChampionshipRaceSameDriver = Participant::factory()
            ->for($raceInDifferentChampionship)
            ->for($raceInDifferentChampionship->championship)
            ->driver([
                'first_name' => 'John',
                'last_name' => 'Racer',
                'licence_number' => 'LN1',
                'bib' => 100,
            ])
            ->create();

        $response = $this
            ->actingAs($user)
            ->get(route('championships.participants.index', $championship));

        $response->assertSuccessful();

        $response->assertViewHas('championship', $championship);

        $response->assertViewHas('uniqueParticipantsCount', 1);

        $response->assertViewHas('participants');

        $participant = $response->viewData('participants')->first();

        $this->assertEquals(101, $participant->bib);
        $this->assertEquals('John', $participant->first_name);
        $this->assertEquals('Racer', $participant->last_name);

        $this->assertEquals(2, $participant->participationHistory->count());

        $this->assertTrue($participant->participationHistory->first()->is($participationToFirstRace));
        $this->assertTrue($participant->participationHistory->last()->is($participationToLastRace));

    }
}

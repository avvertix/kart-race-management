<?php

declare(strict_types=1);

namespace Tests\Feature\Exports;

use App\Models\Participant;
use App\Models\Race;
use App\Models\User;
use Carbon\Carbon;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class PrintRaceParticipantsControllerTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_print_requires_authentication()
    {
        $race = Race::factory()->create();

        $response = $this->get(route('races.participants.print', $race));

        $response->assertRedirect(route('login'));
    }

    public function test_print_returns_participants()
    {
        $user = User::factory()->organizer()->create();

        $race = Race::factory()->create();

        $participant = Participant::factory()
            ->recycle($race->championship)
            ->category()
            ->create([
                'race_id' => $race->getKey(),
            ]);

        $response = $this
            ->actingAs($user)
            ->get(route('races.participants.print', $race));

        $response->assertOk();
        $response->assertViewHas('participants', function ($participants) use ($participant) {
            return $participants->count() === 1 && $participants->first()->is($participant);
        });
    }

    public function test_print_sorts_by_bib_by_default()
    {
        $user = User::factory()->organizer()->create();

        $race = Race::factory()->create();

        $participant1 = Participant::factory()
            ->recycle($race->championship)
            ->category()
            ->create([
                'race_id' => $race->getKey(),
                'bib' => 10,
            ]);

        $participant2 = Participant::factory()
            ->recycle($race->championship)
            ->category()
            ->create([
                'race_id' => $race->getKey(),
                'bib' => 5,
            ]);

        $response = $this
            ->actingAs($user)
            ->get(route('races.participants.print', $race));

        $response->assertOk();
        $response->assertViewHas('participants', function ($participants) use ($participant1, $participant2) {
            return $participants->count() === 2
                && $participants->first()->is($participant2)
                && $participants->last()->is($participant1);
        });
    }

    public function test_print_sorts_by_registration_date()
    {
        $user = User::factory()->organizer()->create();

        $race = Race::factory()->create();

        $participant1 = Participant::factory()
            ->recycle($race->championship)
            ->category()
            ->create([
                'race_id' => $race->getKey(),
                'bib' => 5,
                'created_at' => Carbon::parse('2023-02-28 10:00:00'),
            ]);

        $participant2 = Participant::factory()
            ->recycle($race->championship)
            ->category()
            ->create([
                'race_id' => $race->getKey(),
                'bib' => 10,
                'created_at' => Carbon::parse('2023-02-27 10:00:00'),
            ]);

        $response = $this
            ->actingAs($user)
            ->get(route('races.participants.print', ['race' => $race, 'sort' => 'registration']));

        $response->assertOk();
        $response->assertViewHas('participants', function ($participants) use ($participant1, $participant2) {
            return $participants->count() === 2
                && $participants->first()->is($participant2)
                && $participants->last()->is($participant1);
        });
    }

    public function test_print_filters_by_registration_date_from()
    {
        $user = User::factory()->organizer()->create();

        $race = Race::factory()->create();

        $participant1 = Participant::factory()
            ->recycle($race->championship)
            ->category()
            ->create([
                'race_id' => $race->getKey(),
                'created_at' => Carbon::parse('2023-02-28 10:00:00'),
            ]);

        $participant2 = Participant::factory()
            ->recycle($race->championship)
            ->category()
            ->create([
                'race_id' => $race->getKey(),
                'created_at' => Carbon::parse('2023-02-26 10:00:00'),
            ]);

        $response = $this
            ->actingAs($user)
            ->get(route('races.participants.print', ['race' => $race, 'from' => '2023-02-27']));

        $response->assertOk();
        $response->assertViewHas('participants', function ($participants) use ($participant1) {
            return $participants->count() === 1 && $participants->first()->is($participant1);
        });
    }

    public function test_print_filters_by_registration_date_to()
    {
        $user = User::factory()->organizer()->create();

        $race = Race::factory()->create();

        $participant1 = Participant::factory()
            ->recycle($race->championship)
            ->category()
            ->create([
                'race_id' => $race->getKey(),
                'created_at' => Carbon::parse('2023-02-28 10:00:00'),
            ]);

        $participant2 = Participant::factory()
            ->recycle($race->championship)
            ->category()
            ->create([
                'race_id' => $race->getKey(),
                'created_at' => Carbon::parse('2023-02-26 10:00:00'),
            ]);

        $response = $this
            ->actingAs($user)
            ->get(route('races.participants.print', ['race' => $race, 'to' => '2023-02-27']));

        $response->assertOk();
        $response->assertViewHas('participants', function ($participants) use ($participant2) {
            return $participants->count() === 1 && $participants->first()->is($participant2);
        });
    }

    public function test_print_filters_by_date_range()
    {
        $user = User::factory()->organizer()->create();

        $race = Race::factory()->create();

        $participant1 = Participant::factory()
            ->recycle($race->championship)
            ->category()
            ->create([
                'race_id' => $race->getKey(),
                'created_at' => Carbon::parse('2023-02-25 10:00:00'),
            ]);

        $participant2 = Participant::factory()
            ->recycle($race->championship)
            ->category()
            ->create([
                'race_id' => $race->getKey(),
                'created_at' => Carbon::parse('2023-02-27 10:00:00'),
            ]);

        $participant3 = Participant::factory()
            ->recycle($race->championship)
            ->category()
            ->create([
                'race_id' => $race->getKey(),
                'created_at' => Carbon::parse('2023-03-01 10:00:00'),
            ]);

        $response = $this
            ->actingAs($user)
            ->get(route('races.participants.print', ['race' => $race, 'from' => '2023-02-26', 'to' => '2023-02-28']));

        $response->assertOk();
        $response->assertViewHas('participants', function ($participants) use ($participant2) {
            return $participants->count() === 1 && $participants->first()->is($participant2);
        });
    }

    public function test_print_filters_by_single_participant()
    {
        $user = User::factory()->organizer()->create();

        $race = Race::factory()->create();

        $participant1 = Participant::factory()
            ->recycle($race->championship)
            ->category()
            ->create([
                'race_id' => $race->getKey(),
            ]);

        $participant2 = Participant::factory()
            ->recycle($race->championship)
            ->category()
            ->create([
                'race_id' => $race->getKey(),
            ]);

        $response = $this
            ->actingAs($user)
            ->get(route('races.participants.print', ['race' => $race, 'pid' => $participant1->getKey()]));

        $response->assertOk();
        $response->assertViewHas('participants', function ($participants) use ($participant1) {
            return $participants->count() === 1 && $participants->first()->is($participant1);
        });
    }

    public function test_print_validates_pid_belongs_to_race()
    {
        $user = User::factory()->organizer()->create();

        $race = Race::factory()->create();
        $otherRace = Race::factory()->create();

        $participant = Participant::factory()
            ->recycle($otherRace->championship)
            ->category()
            ->create([
                'race_id' => $otherRace->getKey(),
            ]);

        $response = $this
            ->actingAs($user)
            ->get(route('races.participants.print', ['race' => $race, 'pid' => $participant->getKey()]));

        $response->assertInvalid(['pid']);
    }

    public function test_print_validates_sort_parameter()
    {
        $user = User::factory()->organizer()->create();

        $race = Race::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('races.participants.print', ['race' => $race, 'sort' => 'invalid']));

        $response->assertInvalid(['sort']);
    }

    public function test_print_view_receives_race_and_championship()
    {
        $user = User::factory()->organizer()->create();

        $race = Race::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('races.participants.print', $race));

        $response->assertOk();
        $response->assertViewHas('race', function ($viewRace) use ($race) {
            return $viewRace->is($race);
        });
        $response->assertViewHas('championship', function ($championship) use ($race) {
            return $championship->is($race->championship);
        });
    }
}

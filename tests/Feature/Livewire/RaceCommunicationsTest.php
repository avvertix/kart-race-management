<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\RaceCommunications;
use App\Models\Championship;
use App\Models\ChampionshipPenalty;
use App\Models\CommunicationType;
use App\Models\Race;
use App\Models\RaceCommunication;
use App\Models\RunType;
use App\Models\User;
use Livewire\Livewire;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class RaceCommunicationsTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_page_requires_login(): void
    {
        $race = Race::factory()->create();

        $this->get(route('races.communications.index', $race))
            ->assertRedirectToRoute('login');
    }

    public function test_page_is_accessible_by_organizer(): void
    {
        $user = User::factory()->organizer()->create();
        $race = Race::factory()->create();

        $this->actingAs($user)
            ->get(route('races.communications.index', $race))
            ->assertSuccessful()
            ->assertViewIs('race.communications');
    }

    public function test_page_is_accessible_by_racemanager(): void
    {
        $user = User::factory()->racemanager()->create();
        $race = Race::factory()->create();

        $this->actingAs($user)
            ->get(route('races.communications.index', $race))
            ->assertSuccessful();
    }

    public function test_component_renders(): void
    {
        $user = User::factory()->organizer()->create();
        $race = Race::factory()->create();

        Livewire::actingAs($user)
            ->test(RaceCommunications::class, ['race' => $race])
            ->assertSuccessful();
    }

    public function test_can_post_a_communication(): void
    {
        $user = User::factory()->organizer()->create();
        $race = Race::factory()->create();

        Livewire::actingAs($user)
            ->test(RaceCommunications::class, ['race' => $race])
            ->set('type', 'communication')
            ->set('message', 'Race briefing at 9:00.')
            ->call('post')
            ->assertHasNoErrors()
            ->assertDispatched('posted');

        $this->assertDatabaseHas('race_communications', [
            'race_id' => $race->getKey(),
            'user_id' => $user->getKey(),
            'type' => CommunicationType::Communication->value,
            'message' => 'Race briefing at 9:00.',
        ]);
    }

    public function test_can_post_a_penalty_with_run_type(): void
    {
        $user = User::factory()->organizer()->create();
        $race = Race::factory()->create();

        Livewire::actingAs($user)
            ->test(RaceCommunications::class, ['race' => $race])
            ->set('type', 'penalty')
            ->set('run_type', RunType::RACE_1->value)
            ->set('message', 'Kart 5: 10 second penalty for causing a collision.')
            ->call('post')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('race_communications', [
            'race_id' => $race->getKey(),
            'type' => CommunicationType::Penalty->value,
            'run_type' => RunType::RACE_1->value,
        ]);
    }

    public function test_post_validates_message_required(): void
    {
        $user = User::factory()->organizer()->create();
        $race = Race::factory()->create();

        Livewire::actingAs($user)
            ->test(RaceCommunications::class, ['race' => $race])
            ->set('message', '')
            ->call('post')
            ->assertHasErrors(['message' => 'required']);
    }

    public function test_penalty_templates_are_available_in_component(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();
        $race = Race::factory()->create(['championship_id' => $championship->getKey()]);
        ChampionshipPenalty::factory()->create([
            'championship_id' => $championship->getKey(),
            'title' => 'False Start',
            'description' => 'Ten second time penalty.',
        ]);

        $component = Livewire::actingAs($user)
            ->test(RaceCommunications::class, ['race' => $race]);

        $this->assertCount(1, $component->penalties);
        $this->assertEquals('False Start', $component->penalties->first()->title);
    }

    public function test_can_mark_message_as_read(): void
    {
        $user = User::factory()->organizer()->create();
        $race = Race::factory()->create();
        $communication = RaceCommunication::factory()->create([
            'race_id' => $race->getKey(),
            'championship_id' => $race->championship_id,
            'read_at' => null,
        ]);

        Livewire::actingAs($user)
            ->test(RaceCommunications::class, ['race' => $race])
            ->call('toggleRead', $communication->ulid);

        $this->assertNotNull($communication->fresh()->read_at);
    }

    public function test_can_toggle_message_back_to_unread(): void
    {
        $user = User::factory()->organizer()->create();
        $race = Race::factory()->create();
        $communication = RaceCommunication::factory()->create([
            'race_id' => $race->getKey(),
            'championship_id' => $race->championship_id,
            'read_at' => now(),
        ]);

        Livewire::actingAs($user)
            ->test(RaceCommunications::class, ['race' => $race])
            ->call('toggleRead', $communication->ulid);

        $this->assertNull($communication->fresh()->read_at);
    }

    public function test_author_can_delete_own_message(): void
    {
        $user = User::factory()->organizer()->create();
        $race = Race::factory()->create();
        $communication = RaceCommunication::factory()->create([
            'race_id' => $race->getKey(),
            'championship_id' => $race->championship_id,
            'user_id' => $user->getKey(),
        ]);

        Livewire::actingAs($user)
            ->test(RaceCommunications::class, ['race' => $race])
            ->call('delete', $communication->ulid);

        $this->assertDatabaseMissing('race_communications', [
            'ulid' => $communication->ulid,
        ]);
    }

    public function test_other_user_cannot_delete_message(): void
    {
        $author = User::factory()->organizer()->create();
        $other = User::factory()->organizer()->create();
        $race = Race::factory()->create();
        $communication = RaceCommunication::factory()->create([
            'race_id' => $race->getKey(),
            'championship_id' => $race->championship_id,
            'user_id' => $author->getKey(),
        ]);

        Livewire::actingAs($other)
            ->test(RaceCommunications::class, ['race' => $race])
            ->call('delete', $communication->ulid)
            ->assertForbidden();
    }
}

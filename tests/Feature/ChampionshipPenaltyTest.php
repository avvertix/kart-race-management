<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\ChampionshipPenalty;
use App\Models\User;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class ChampionshipPenaltyTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_listing_requires_login(): void
    {
        $championship = Championship::factory()->create();

        $this->get(route('championships.penalties.index', $championship))
            ->assertRedirectToRoute('login');
    }

    public function test_penalties_can_be_listed_by_organizer(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();
        ChampionshipPenalty::factory()->create([
            'championship_id' => $championship->getKey(),
            'title' => 'False Start',
        ]);

        $this->actingAs($user)
            ->get(route('championships.penalties.index', $championship))
            ->assertSuccessful()
            ->assertViewIs('championship.penalty.index')
            ->assertSee('False Start');
    }

    public function test_create_form_requires_organizer(): void
    {
        $user = User::factory()->racemanager()->create();
        $championship = Championship::factory()->create();

        $this->actingAs($user)
            ->get(route('championships.penalties.create', $championship))
            ->assertForbidden();
    }

    public function test_create_form_shown_for_organizer(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        $this->actingAs($user)
            ->get(route('championships.penalties.create', $championship))
            ->assertSuccessful()
            ->assertViewIs('championship.penalty.create');
    }

    public function test_penalty_can_be_created(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        $this->actingAs($user)
            ->post(route('championships.penalties.store', $championship), [
                'title' => 'False Start',
                'description' => 'Ten second penalty for a false start.',
            ])
            ->assertRedirectToRoute('championships.penalties.index', $championship);

        $this->assertDatabaseHas('championship_penalties', [
            'championship_id' => $championship->getKey(),
            'title' => 'False Start',
        ]);
    }

    public function test_penalty_creation_validates_title_required(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        $this->actingAs($user)
            ->post(route('championships.penalties.store', $championship), [
                'title' => '',
            ])
            ->assertSessionHasErrors('title');
    }

    public function test_penalty_can_be_edited(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();
        $penalty = ChampionshipPenalty::factory()->create([
            'championship_id' => $championship->getKey(),
            'title' => 'Old title',
        ]);

        $this->actingAs($user)
            ->get(route('penalties.edit', $penalty))
            ->assertSuccessful()
            ->assertViewIs('championship.penalty.edit');
    }

    public function test_penalty_can_be_updated(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();
        $penalty = ChampionshipPenalty::factory()->create([
            'championship_id' => $championship->getKey(),
            'title' => 'Old title',
        ]);

        $this->actingAs($user)
            ->put(route('penalties.update', $penalty), [
                'title' => 'Updated title',
                'description' => 'Updated description.',
            ])
            ->assertRedirectToRoute('championships.penalties.index', $championship);

        $this->assertDatabaseHas('championship_penalties', [
            'ulid' => $penalty->ulid,
            'title' => 'Updated title',
        ]);
    }

    public function test_penalty_can_be_deleted(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();
        $penalty = ChampionshipPenalty::factory()->create([
            'championship_id' => $championship->getKey(),
        ]);

        $this->actingAs($user)
            ->delete(route('penalties.destroy', $penalty))
            ->assertRedirectToRoute('championships.penalties.index', $championship);

        $this->assertDatabaseMissing('championship_penalties', [
            'ulid' => $penalty->ulid,
        ]);
    }

    public function test_racemanager_cannot_delete_penalty(): void
    {
        $user = User::factory()->racemanager()->create();
        $championship = Championship::factory()->create();
        $penalty = ChampionshipPenalty::factory()->create([
            'championship_id' => $championship->getKey(),
        ]);

        $this->actingAs($user)
            ->delete(route('penalties.destroy', $penalty))
            ->assertForbidden();
    }
}

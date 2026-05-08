<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\User;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class ChampionshipPenaltyImportControllerTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_import_form_requires_login(): void
    {
        $championship = Championship::factory()->create();

        $this->get(route('championships.penalties.import.create', $championship))
            ->assertRedirectToRoute('login');
    }

    public function test_import_form_requires_organizer(): void
    {
        $user = User::factory()->racemanager()->create();
        $championship = Championship::factory()->create();

        $this->actingAs($user)
            ->get(route('championships.penalties.import.create', $championship))
            ->assertForbidden();
    }

    public function test_import_form_shown_for_organizer(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        $this->actingAs($user)
            ->get(route('championships.penalties.import.create', $championship))
            ->assertSuccessful()
            ->assertViewIs('championship.penalty.import');
    }

    public function test_penalties_can_be_imported(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        $csv = implode("\n", [
            'False Start;Ten second penalty for anticipating the start.',
            'Unsafe Driving;',
        ]);

        $this->actingAs($user)
            ->post(route('championships.penalties.import.store', $championship), [
                'penalties' => $csv,
            ])
            ->assertRedirectToRoute('championships.penalties.index', $championship);

        $this->assertDatabaseHas('championship_penalties', [
            'championship_id' => $championship->getKey(),
            'title' => 'False Start',
            'description' => 'Ten second penalty for anticipating the start.',
        ]);

        $this->assertDatabaseHas('championship_penalties', [
            'championship_id' => $championship->getKey(),
            'title' => 'Unsafe Driving',
            'description' => null,
        ]);

        $this->assertDatabaseCount('championship_penalties', 2);
    }

    public function test_import_skips_blank_lines(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        $csv = "False Start;Description\n\n\nUnsafe Driving;";

        $this->actingAs($user)
            ->post(route('championships.penalties.import.store', $championship), [
                'penalties' => $csv,
            ])
            ->assertRedirectToRoute('championships.penalties.index', $championship);

        $this->assertDatabaseCount('championship_penalties', 2);
    }

    public function test_import_validates_title_required(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        $this->actingAs($user)
            ->post(route('championships.penalties.import.store', $championship), [
                'penalties' => ';Only a description, no title',
            ])
            ->assertSessionHasErrors('penalties');
    }

    public function test_import_validates_input_required(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        $this->actingAs($user)
            ->post(route('championships.penalties.import.store', $championship), [
                'penalties' => '',
            ])
            ->assertSessionHasErrors('penalties');
    }
}

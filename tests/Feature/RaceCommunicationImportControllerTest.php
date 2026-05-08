<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\CommunicationType;
use App\Models\Race;
use App\Models\RunType;
use App\Models\User;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class RaceCommunicationImportControllerTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_import_form_requires_login(): void
    {
        $race = Race::factory()->create();

        $this->get(route('races.communications.import.create', $race))
            ->assertRedirectToRoute('login');
    }

    public function test_import_form_shown_for_organizer(): void
    {
        $user = User::factory()->organizer()->create();
        $race = Race::factory()->create();

        $this->actingAs($user)
            ->get(route('races.communications.import.create', $race))
            ->assertSuccessful()
            ->assertViewIs('race.communications-import');
    }

    public function test_communications_can_be_imported(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();
        $race = Race::factory()->create(['championship_id' => $championship->getKey()]);

        $csv = implode("\n", [
            'communication;;Race briefing at 09:00.',
            'penalty;race 1;Kart 5: 10 second penalty.',
        ]);

        $this->actingAs($user)
            ->post(route('races.communications.import.store', $race), [
                'communications' => $csv,
            ])
            ->assertRedirectToRoute('races.communications.index', $race);

        $this->assertDatabaseHas('race_communications', [
            'race_id' => $race->getKey(),
            'type' => CommunicationType::Communication->value,
            'run_type' => null,
            'message' => 'Race briefing at 09:00.',
        ]);

        $this->assertDatabaseHas('race_communications', [
            'race_id' => $race->getKey(),
            'type' => CommunicationType::Penalty->value,
            'run_type' => RunType::RACE_1->value,
            'message' => 'Kart 5: 10 second penalty.',
        ]);

        $this->assertDatabaseCount('race_communications', 2);
    }

    public function test_import_accepts_italian_type_labels(): void
    {
        $user = User::factory()->organizer()->create();
        $race = Race::factory()->create();

        $csv = implode("\n", [
            'comunicazione;;Briefing alle 09:00.',
            'penalità;gara 1;Kart 5: penalità 10 secondi.',
        ]);

        $this->actingAs($user)
            ->post(route('races.communications.import.store', $race), [
                'communications' => $csv,
            ])
            ->assertRedirectToRoute('races.communications.index', $race);

        $this->assertDatabaseHas('race_communications', [
            'type' => CommunicationType::Communication->value,
        ]);

        $this->assertDatabaseHas('race_communications', [
            'type' => CommunicationType::Penalty->value,
            'run_type' => RunType::RACE_1->value,
        ]);
    }

    public function test_import_skips_blank_lines(): void
    {
        $user = User::factory()->organizer()->create();
        $race = Race::factory()->create();

        $csv = "communication;;First message.\n\n\ncommunication;;Second message.";

        $this->actingAs($user)
            ->post(route('races.communications.import.store', $race), [
                'communications' => $csv,
            ])
            ->assertRedirectToRoute('races.communications.index', $race);

        $this->assertDatabaseCount('race_communications', 2);
    }

    public function test_import_validates_message_required(): void
    {
        $user = User::factory()->organizer()->create();
        $race = Race::factory()->create();

        $this->actingAs($user)
            ->post(route('races.communications.import.store', $race), [
                'communications' => 'communication;;',
            ])
            ->assertSessionHasErrors('communications');
    }

    public function test_import_validates_type_required(): void
    {
        $user = User::factory()->organizer()->create();
        $race = Race::factory()->create();

        $this->actingAs($user)
            ->post(route('races.communications.import.store', $race), [
                'communications' => ';;A message with no type.',
            ])
            ->assertSessionHasErrors('communications');
    }

    public function test_import_validates_input_required(): void
    {
        $user = User::factory()->organizer()->create();
        $race = Race::factory()->create();

        $this->actingAs($user)
            ->post(route('races.communications.import.store', $race), [
                'communications' => '',
            ])
            ->assertSessionHasErrors('communications');
    }
}

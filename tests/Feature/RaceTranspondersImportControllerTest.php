<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\Race;
use App\Models\Transponder;
use App\Models\User;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class RaceTranspondersImportControllerTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_import_form_requires_login(): void
    {
        $race = Race::factory()->create();

        $this->get(route('races.transponders.import.create', $race))
            ->assertRedirectToRoute('login');
    }

    public function test_import_form_shown_for_organizer(): void
    {
        $user = User::factory()->organizer()->create();
        $race = Race::factory()->create();

        $this->actingAs($user)
            ->get(route('races.transponders.import.create', $race))
            ->assertSuccessful()
            ->assertViewIs('race.transponders-import');
    }

    public function test_transponders_can_be_imported(): void
    {
        $user = User::factory()->organizer()->create();
        $race = Race::factory()->create();

        $participant1 = Participant::factory()->create(['race_id' => $race->getKey(), 'racer_hash' => 'AB123456']);
        $participant2 = Participant::factory()->create(['race_id' => $race->getKey(), 'racer_hash' => 'CD789012']);

        $csv = implode("\n", [
            'AB123456;1234567',
            'CD789012;9876543',
        ]);

        $this->actingAs($user)
            ->post(route('races.transponders.import.store', $race), [
                'transponders' => $csv,
            ])
            ->assertRedirectToRoute('races.transponders', $race);

        $this->assertDatabaseHas('transponders', [
            'participant_id' => $participant1->getKey(),
            'race_id' => $race->getKey(),
            'code' => '1234567',
        ]);

        $this->assertDatabaseHas('transponders', [
            'participant_id' => $participant2->getKey(),
            'race_id' => $race->getKey(),
            'code' => '9876543',
        ]);

        $this->assertDatabaseCount('transponders', 2);
    }

    public function test_import_skips_blank_lines(): void
    {
        $user = User::factory()->organizer()->create();
        $race = Race::factory()->create();

        Participant::factory()->create(['race_id' => $race->getKey(), 'racer_hash' => 'AB123456']);
        Participant::factory()->create(['race_id' => $race->getKey(), 'racer_hash' => 'CD789012']);

        $csv = "AB123456;1234567\n\n\nCD789012;9876543";

        $this->actingAs($user)
            ->post(route('races.transponders.import.store', $race), [
                'transponders' => $csv,
            ])
            ->assertRedirectToRoute('races.transponders', $race);

        $this->assertDatabaseCount('transponders', 2);
    }

    public function test_import_fails_when_racer_hash_not_found_in_race(): void
    {
        $user = User::factory()->organizer()->create();
        $race = Race::factory()->create();

        $this->actingAs($user)
            ->post(route('races.transponders.import.store', $race), [
                'transponders' => 'ZZZZZZZZ;1234567',
            ])
            ->assertSessionHasErrors('transponders');

        $this->assertDatabaseCount('transponders', 0);
    }

    public function test_import_fails_when_transponder_code_already_assigned_in_race(): void
    {
        $user = User::factory()->organizer()->create();
        $race = Race::factory()->create();

        $participant1 = Participant::factory()->create(['race_id' => $race->getKey(), 'racer_hash' => 'AB123456']);
        Transponder::factory()->create(['race_id' => $race->getKey(), 'participant_id' => $participant1->getKey(), 'code' => '1234567']);

        Participant::factory()->create(['race_id' => $race->getKey(), 'racer_hash' => 'CD789012']);

        $this->actingAs($user)
            ->post(route('races.transponders.import.store', $race), [
                'transponders' => 'CD789012;1234567',
            ])
            ->assertSessionHasErrors('transponders');

        $this->assertDatabaseCount('transponders', 1);
    }

    public function test_import_fails_when_duplicate_code_in_same_file(): void
    {
        $user = User::factory()->organizer()->create();
        $race = Race::factory()->create();

        Participant::factory()->create(['race_id' => $race->getKey(), 'racer_hash' => 'AB123456']);
        Participant::factory()->create(['race_id' => $race->getKey(), 'racer_hash' => 'CD789012']);

        $csv = implode("\n", [
            'AB123456;1234567',
            'CD789012;1234567',
        ]);

        $this->actingAs($user)
            ->post(route('races.transponders.import.store', $race), [
                'transponders' => $csv,
            ])
            ->assertSessionHasErrors('transponders');

        $this->assertDatabaseCount('transponders', 0);
    }

    public function test_import_validates_transponder_code_is_numeric(): void
    {
        $user = User::factory()->organizer()->create();
        $race = Race::factory()->create();

        Participant::factory()->create(['race_id' => $race->getKey(), 'racer_hash' => 'AB123456']);

        $this->actingAs($user)
            ->post(route('races.transponders.import.store', $race), [
                'transponders' => 'AB123456;ABCDEFG',
            ])
            ->assertSessionHasErrors('transponders');

        $this->assertDatabaseCount('transponders', 0);
    }

    public function test_import_validates_input_required(): void
    {
        $user = User::factory()->organizer()->create();
        $race = Race::factory()->create();

        $this->actingAs($user)
            ->post(route('races.transponders.import.store', $race), [
                'transponders' => '',
            ])
            ->assertSessionHasErrors('transponders');
    }
}

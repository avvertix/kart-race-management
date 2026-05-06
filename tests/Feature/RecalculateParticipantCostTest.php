<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Data\RegistrationCostData;
use App\Models\Category;
use App\Models\Championship;
use App\Models\Participant;
use App\Models\Race;
use App\Notifications\UpdateParticipantRegistration;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class RecalculateParticipantCostTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_dry_run_prints_comparison_without_saving(): void
    {
        config(['races.price' => 15000]);

        $championship = Championship::factory()->create();
        $race = Race::factory()->create(['championship_id' => $championship->id]);
        $category = Category::factory()->withPrice(20000)->create(['championship_id' => $championship->id]);

        $participant = Participant::factory()->create([
            'championship_id' => $championship->id,
            'race_id' => $race->id,
            'category_id' => $category->id,
            'cost' => new RegistrationCostData(registration_cost: 15000),
        ]);

        $this->artisan('participants:recalculate-cost', [
            'race' => $race->uuid,
            '--dry-run' => true,
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('Dry-run mode')
            ->expectsOutputToContain('1 of 1 participant(s) have a different cost.');

        // Cost must not have been updated
        $this->assertEquals(15000, $participant->fresh()->cost->registration_cost);
    }

    public function test_recalculates_and_saves_costs(): void
    {
        config(['races.price' => 15000]);

        $championship = Championship::factory()->create();
        $race = Race::factory()->create(['championship_id' => $championship->id]);
        $category = Category::factory()->withPrice(20000)->create(['championship_id' => $championship->id]);

        $participant = Participant::factory()->create([
            'championship_id' => $championship->id,
            'race_id' => $race->id,
            'category_id' => $category->id,
            'cost' => new RegistrationCostData(registration_cost: 15000),
        ]);

        $this->artisan('participants:recalculate-cost', [
            'race' => $race->uuid,
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('1 of 1 participant(s) have a different cost.');

        $this->assertEquals(20000, $participant->fresh()->cost->registration_cost);
    }

    public function test_reports_no_change_when_cost_already_correct(): void
    {
        config(['races.price' => 15000]);

        $championship = Championship::factory()->create();
        $race = Race::factory()->create(['championship_id' => $championship->id]);
        $category = Category::factory()->withPrice(15000)->create(['championship_id' => $championship->id]);

        Participant::factory()->create([
            'championship_id' => $championship->id,
            'race_id' => $race->id,
            'category_id' => $category->id,
            'cost' => new RegistrationCostData(registration_cost: 15000),
        ]);

        $this->artisan('participants:recalculate-cost', [
            'race' => $race->uuid,
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('0 of 1 participant(s) have a different cost.');
    }

    public function test_warns_when_no_participants_found(): void
    {
        $race = Race::factory()->create();

        $this->artisan('participants:recalculate-cost', [
            'race' => $race->uuid,
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('No participants found');
    }

    public function test_fails_with_invalid_race_uuid(): void
    {
        $this->artisan('participants:recalculate-cost', [
            'race' => 'invalid-uuid',
        ])->assertFailed();
    }

    public function test_notify_participant_flag_sends_notification_to_changed_participants(): void
    {
        Notification::fake();
        config(['races.price' => 15000]);

        $championship = Championship::factory()->create();
        $race = Race::factory()->create(['championship_id' => $championship->id]);
        $category = Category::factory()->withPrice(20000)->create(['championship_id' => $championship->id]);

        $participant = Participant::factory()->create([
            'championship_id' => $championship->id,
            'race_id' => $race->id,
            'category_id' => $category->id,
            'cost' => new RegistrationCostData(registration_cost: 15000),
        ]);

        $this->artisan('participants:recalculate-cost', [
            'race' => $race->uuid,
            '--notify-participant' => true,
        ])->assertSuccessful();

        Notification::assertSentTo($participant, UpdateParticipantRegistration::class);
    }

    public function test_notify_participant_flag_does_not_send_notification_without_flag(): void
    {
        Notification::fake();
        config(['races.price' => 15000]);

        $championship = Championship::factory()->create();
        $race = Race::factory()->create(['championship_id' => $championship->id]);
        $category = Category::factory()->withPrice(20000)->create(['championship_id' => $championship->id]);

        Participant::factory()->create([
            'championship_id' => $championship->id,
            'race_id' => $race->id,
            'category_id' => $category->id,
            'cost' => new RegistrationCostData(registration_cost: 15000),
        ]);

        $this->artisan('participants:recalculate-cost', [
            'race' => $race->uuid,
        ])->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_json_flag_outputs_changed_participant_uuids(): void
    {
        config(['races.price' => 15000]);

        $championship = Championship::factory()->create();
        $race = Race::factory()->create(['championship_id' => $championship->id]);
        $category = Category::factory()->withPrice(20000)->create(['championship_id' => $championship->id]);

        $participant = Participant::factory()->create([
            'championship_id' => $championship->id,
            'race_id' => $race->id,
            'category_id' => $category->id,
            'cost' => new RegistrationCostData(registration_cost: 15000),
        ]);

        Storage::fake();

        $this->artisan('participants:recalculate-cost', [
            'race' => $race->uuid,
            '--json' => true,
        ])
            ->assertSuccessful();

        Storage::assertExists('changed-participants.json');

        $this->assertEquals([$participant->uuid], Storage::json('changed-participants.json'));
    }

    public function test_json_flag_works_in_dry_run_mode(): void
    {
        config(['races.price' => 15000]);

        $championship = Championship::factory()->create();
        $race = Race::factory()->create(['championship_id' => $championship->id]);
        $category = Category::factory()->withPrice(20000)->create(['championship_id' => $championship->id]);

        $participant = Participant::factory()->create([
            'championship_id' => $championship->id,
            'race_id' => $race->id,
            'category_id' => $category->id,
            'cost' => new RegistrationCostData(registration_cost: 15000),
        ]);

        Storage::fake();

        $this->artisan('participants:recalculate-cost', [
            'race' => $race->uuid,
            '--dry-run' => true,
            '--json' => true,
        ])
            ->assertSuccessful();

        // Cost must still not have been updated in dry-run
        $this->assertEquals(15000, $participant->fresh()->cost->registration_cost);

        Storage::assertExists('changed-participants.json');

        $this->assertEquals([$participant->uuid], Storage::json('changed-participants.json'));
    }

    public function test_json_flag_outputs_empty_array_when_no_cost_changed(): void
    {
        config(['races.price' => 15000]);

        $championship = Championship::factory()->create();
        $race = Race::factory()->create(['championship_id' => $championship->id]);
        $category = Category::factory()->withPrice(15000)->create(['championship_id' => $championship->id]);

        Participant::factory()->create([
            'championship_id' => $championship->id,
            'race_id' => $race->id,
            'category_id' => $category->id,
            'cost' => new RegistrationCostData(registration_cost: 15000),
        ]);

        Storage::fake();

        $this->artisan('participants:recalculate-cost', [
            'race' => $race->uuid,
            '--json' => true,
        ])
            ->assertSuccessful();

        Storage::assertExists('changed-participants.json');

        $this->assertEquals([], Storage::json('changed-participants.json'));
    }

    public function test_notify_participant_flag_does_not_send_notification_in_dry_run(): void
    {
        Notification::fake();
        config(['races.price' => 15000]);

        $championship = Championship::factory()->create();
        $race = Race::factory()->create(['championship_id' => $championship->id]);
        $category = Category::factory()->withPrice(20000)->create(['championship_id' => $championship->id]);

        Participant::factory()->create([
            'championship_id' => $championship->id,
            'race_id' => $race->id,
            'category_id' => $category->id,
            'cost' => new RegistrationCostData(registration_cost: 15000),
        ]);

        $this->artisan('participants:recalculate-cost', [
            'race' => $race->uuid,
            '--dry-run' => true,
            '--notify-participant' => true,
        ])->assertSuccessful();

        Notification::assertNothingSent();
    }
}

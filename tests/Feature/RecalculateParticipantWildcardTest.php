<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\Participant;
use App\Models\Race;
use App\Models\WildcardStrategy;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class RecalculateParticipantWildcardTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_fails_with_invalid_race_uuid(): void
    {
        $this->artisan('participants:recalculate-wildcard', [
            'race' => 'invalid-uuid',
        ])->assertFailed();
    }

    public function test_warns_when_wildcard_not_enabled(): void
    {
        $championship = Championship::factory()->create([
            'wildcard' => ['enabled' => false, 'strategy' => WildcardStrategy::BASED_ON_BIB_RESERVATION],
        ]);
        $race = Race::factory()->recycle($championship)->create();

        $this->artisan('participants:recalculate-wildcard', [
            'race' => $race->uuid,
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('Wildcard is not enabled');
    }

    public function test_warns_when_no_participants_found(): void
    {
        $championship = Championship::factory()->create([
            'wildcard' => ['enabled' => true, 'strategy' => WildcardStrategy::BASED_ON_BIB_RESERVATION],
        ]);
        $race = Race::factory()->recycle($championship)->create();

        $this->artisan('participants:recalculate-wildcard', [
            'race' => $race->uuid,
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('No participants found');
    }

    public function test_dry_run_prints_comparison_without_saving(): void
    {
        $championship = Championship::factory()->create([
            'wildcard' => ['enabled' => true, 'strategy' => WildcardStrategy::BASED_ON_BIB_RESERVATION],
        ]);

        $race = Race::factory()->recycle($championship)->create();

        $participant = Participant::factory()->recycle($championship)->recycle($race)->create([
            'wildcard' => false,
        ]);

        $this->artisan('participants:recalculate-wildcard', [
            'race' => $race->uuid,
            '--dry-run' => true,
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('Dry-run mode')
            ->expectsOutputToContain('1 of 1 participant(s) have a different wildcard status.');

        $this->assertFalse($participant->fresh()->wildcard);
    }

    public function test_recalculates_and_saves_wildcard_status(): void
    {
        $championship = Championship::factory()->create([
            'wildcard' => ['enabled' => true, 'strategy' => WildcardStrategy::BASED_ON_BIB_RESERVATION],
        ]);

        $race = Race::factory()->recycle($championship)->create();

        // Participant has no BIB reservation → should be wildcard (true)
        $participant = Participant::factory()->recycle($championship)->recycle($race)->create([
            'wildcard' => false,
        ]);

        $this->artisan('participants:recalculate-wildcard', [
            'race' => $race->uuid,
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('1 of 1 participant(s) have a different wildcard status.');

        $this->assertTrue($participant->fresh()->wildcard);
    }

    public function test_reports_no_change_when_wildcard_status_already_correct(): void
    {
        $championship = Championship::factory()->create([
            'wildcard' => ['enabled' => true, 'strategy' => WildcardStrategy::BASED_ON_BIB_RESERVATION],
        ]);

        $race = Race::factory()->recycle($championship)->create();

        // Participant has no BIB reservation → wildcard is true; pre-set it to true so no change
        Participant::factory()->recycle($championship)->recycle($race)->create([
            'wildcard' => true,
        ]);

        $this->artisan('participants:recalculate-wildcard', [
            'race' => $race->uuid,
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('0 of 1 participant(s) have a different wildcard status.');
    }

    public function test_dry_run_does_not_save_changes(): void
    {
        $championship = Championship::factory()->create([
            'wildcard' => ['enabled' => true, 'strategy' => WildcardStrategy::BASED_ON_BIB_RESERVATION],
        ]);

        $race = Race::factory()->recycle($championship)->create();

        $participant = Participant::factory()->recycle($championship)->recycle($race)->create([
            'wildcard' => false,
        ]);

        $this->artisan('participants:recalculate-wildcard', [
            'race' => $race->uuid,
            '--dry-run' => true,
        ])->assertSuccessful();

        $this->assertFalse($participant->fresh()->wildcard);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\AssignPointsButton;
use App\Models\ChampionshipPointScheme;
use App\Models\ParticipantResult;
use App\Models\Race;
use App\Models\ResultStatus;
use App\Models\RunResult;
use App\Models\RunType;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class AssignPointsButtonTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_single_scheme_assigns_immediately_without_modal(): void
    {
        $race = Race::factory()->create();

        $pointScheme = ChampionshipPointScheme::factory()->create([
            'championship_id' => $race->championship_id,
        ]);

        $runResult = RunResult::factory()->create([
            'race_id' => $race->getKey(),
            'run_type' => RunType::RACE_1,
        ]);

        $participantResult = ParticipantResult::factory()->create([
            'run_result_id' => $runResult->getKey(),
            'position_in_category' => '1',
            'category' => 'Senior',
            'status' => ResultStatus::FINISHED,
        ]);

        // Add more participants to avoid small category modifier
        for ($i = 2; $i <= 5; $i++) {
            ParticipantResult::factory()->create([
                'run_result_id' => $runResult->getKey(),
                'position_in_category' => (string) $i,
                'category' => 'Senior',
                'status' => ResultStatus::FINISHED,
            ]);
        }

        Livewire::test(AssignPointsButton::class, ['race' => $race, 'runResult' => $runResult])
            ->call('openAssignPoints')
            ->assertSet('showModal', false)
            ->assertRedirect();

        $participantResult->refresh();
        $this->assertEquals(25, $participantResult->points);
    }

    public function test_multiple_schemes_show_modal(): void
    {
        $race = Race::factory()->create();

        ChampionshipPointScheme::factory()->create([
            'championship_id' => $race->championship_id,
            'name' => 'Scheme A',
        ]);

        ChampionshipPointScheme::factory()->create([
            'championship_id' => $race->championship_id,
            'name' => 'Scheme B',
        ]);

        $runResult = RunResult::factory()->create([
            'race_id' => $race->getKey(),
        ]);

        Livewire::test(AssignPointsButton::class, ['race' => $race, 'runResult' => $runResult])
            ->call('openAssignPoints')
            ->assertSet('showModal', true)
            ->assertSee('Scheme A')
            ->assertSee('Scheme B');
    }

    public function test_assigns_points_after_scheme_selection(): void
    {
        $race = Race::factory()->create();

        $schemeA = ChampionshipPointScheme::factory()->create([
            'championship_id' => $race->championship_id,
            'name' => 'Scheme A',
        ]);

        ChampionshipPointScheme::factory()->create([
            'championship_id' => $race->championship_id,
            'name' => 'Scheme B',
        ]);

        $runResult = RunResult::factory()->create([
            'race_id' => $race->getKey(),
            'run_type' => RunType::RACE_1,
        ]);

        $participantResult = ParticipantResult::factory()->create([
            'run_result_id' => $runResult->getKey(),
            'position_in_category' => '1',
            'category' => 'Senior',
            'status' => ResultStatus::FINISHED,
        ]);

        for ($i = 2; $i <= 5; $i++) {
            ParticipantResult::factory()->create([
                'run_result_id' => $runResult->getKey(),
                'position_in_category' => (string) $i,
                'category' => 'Senior',
                'status' => ResultStatus::FINISHED,
            ]);
        }

        Livewire::test(AssignPointsButton::class, ['race' => $race, 'runResult' => $runResult])
            ->call('openAssignPoints')
            ->assertSet('showModal', true)
            ->set('selectedPointScheme', (string) $schemeA->getKey())
            ->call('assignPoints')
            ->assertRedirect();

        $participantResult->refresh();
        $this->assertEquals(25, $participantResult->points);
    }

    public function test_assign_all_dispatches_job(): void
    {
        Queue::fake();

        $race = Race::factory()->create();

        $pointScheme = ChampionshipPointScheme::factory()->create([
            'championship_id' => $race->championship_id,
        ]);

        RunResult::factory()->create(['race_id' => $race->getKey()]);

        Livewire::test(AssignPointsButton::class, ['race' => $race])
            ->call('openAssignPoints')
            ->assertRedirect();

        Queue::assertPushed(\App\Jobs\AssignPointsToRaceResults::class);
    }

    public function test_no_point_schemes_shows_error(): void
    {
        $race = Race::factory()->create();

        $runResult = RunResult::factory()->create([
            'race_id' => $race->getKey(),
        ]);

        Livewire::test(AssignPointsButton::class, ['race' => $race, 'runResult' => $runResult])
            ->call('openAssignPoints')
            ->assertSet('showModal', false)
            ->assertNoRedirect();
    }
}

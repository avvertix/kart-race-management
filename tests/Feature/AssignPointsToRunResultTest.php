<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\AssignPointsToRunResult;
use App\Models\ChampionshipPointScheme;
use App\Models\ParticipantResult;
use App\Models\Race;
use App\Models\ResultStatus;
use App\Models\RunResult;
use App\Models\RunType;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class AssignPointsToRunResultTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_assigns_points_based_on_position_in_category(): void
    {
        $race = Race::factory()->create();

        $pointScheme = ChampionshipPointScheme::factory()->create([
            'championship_id' => $race->championship_id,
        ]);

        $runResult = RunResult::factory()->create([
            'race_id' => $race->getKey(),
            'run_type' => RunType::RACE_1,
        ]);

        $participants = [];
        for ($i = 1; $i <= 5; $i++) {
            $participants[] = ParticipantResult::factory()->create([
                'run_result_id' => $runResult->getKey(),
                'position_in_category' => (string) $i,
                'category' => 'Senior',
                'status' => ResultStatus::FINISHED,
            ]);
        }

        $action = new AssignPointsToRunResult;
        $action($runResult, $pointScheme);

        $participants[0]->refresh();
        $participants[1]->refresh();
        $participants[2]->refresh();

        $this->assertEquals(25, $participants[0]->points);
        $this->assertEquals(18, $participants[1]->points);
        $this->assertEquals(15, $participants[2]->points);
    }

    public function test_assigns_zero_points_for_dnf_with_fixed_mode(): void
    {
        $race = Race::factory()->create();

        $pointScheme = ChampionshipPointScheme::factory()->create([
            'championship_id' => $race->championship_id,
        ]);

        $runResult = RunResult::factory()->create([
            'race_id' => $race->getKey(),
            'run_type' => RunType::RACE_1,
        ]);

        $dnf = ParticipantResult::factory()->create([
            'run_result_id' => $runResult->getKey(),
            'position_in_category' => '5',
            'category' => 'Senior',
            'status' => ResultStatus::DID_NOT_FINISH,
            'is_dnf' => true,
        ]);

        $action = new AssignPointsToRunResult;
        $action($runResult, $pointScheme);

        $dnf->refresh();
        $this->assertEquals(0, $dnf->points);
    }

    public function test_applies_rain_modifier(): void
    {
        $race = Race::factory()->create([
            'rain' => true,
        ]);

        $pointScheme = ChampionshipPointScheme::factory()->create([
            'championship_id' => $race->championship_id,
        ]);

        $runResult = RunResult::factory()->create([
            'race_id' => $race->getKey(),
            'run_type' => RunType::RACE_1,
        ]);

        // Need enough participants to avoid small category modifier
        $participants = [];
        for ($i = 1; $i <= 5; $i++) {
            $participants[] = ParticipantResult::factory()->create([
                'run_result_id' => $runResult->getKey(),
                'position_in_category' => (string) $i,
                'category' => 'Senior',
                'status' => ResultStatus::FINISHED,
            ]);
        }

        $action = new AssignPointsToRunResult;
        $action($runResult, $pointScheme);

        $participants[0]->refresh();
        // 25 * (1 - 50/100) = 12.5
        $this->assertEquals(12.5, $participants[0]->points);
    }

    public function test_applies_point_multiplier(): void
    {
        $race = Race::factory()->create([
            'point_multiplier' => 2.0,
        ]);

        $pointScheme = ChampionshipPointScheme::factory()->create([
            'championship_id' => $race->championship_id,
        ]);

        $runResult = RunResult::factory()->create([
            'race_id' => $race->getKey(),
            'run_type' => RunType::RACE_1,
        ]);

        $participants = [];
        for ($i = 1; $i <= 5; $i++) {
            $participants[] = ParticipantResult::factory()->create([
                'run_result_id' => $runResult->getKey(),
                'position_in_category' => (string) $i,
                'category' => 'Senior',
                'status' => ResultStatus::FINISHED,
            ]);
        }

        $action = new AssignPointsToRunResult;
        $action($runResult, $pointScheme);

        $participants[0]->refresh();
        // 25 * 2 = 50
        $this->assertEquals(50, $participants[0]->points);
    }

    public function test_applies_small_category_modifier(): void
    {
        $race = Race::factory()->create();

        $pointScheme = ChampionshipPointScheme::factory()->create([
            'championship_id' => $race->championship_id,
        ]);

        $runResult = RunResult::factory()->create([
            'race_id' => $race->getKey(),
            'run_type' => RunType::RACE_1,
        ]);

        // Only 2 participants in category (below default threshold of 3)
        $first = ParticipantResult::factory()->create([
            'run_result_id' => $runResult->getKey(),
            'position_in_category' => '1',
            'category' => 'SmallCat',
            'status' => ResultStatus::FINISHED,
        ]);

        $second = ParticipantResult::factory()->create([
            'run_result_id' => $runResult->getKey(),
            'position_in_category' => '2',
            'category' => 'SmallCat',
            'status' => ResultStatus::FINISHED,
        ]);

        $action = new AssignPointsToRunResult;
        $action($runResult, $pointScheme);

        $first->refresh();
        // 25 * (1 + (-50/100)) = 25 * 0.5 = 12.5
        $this->assertEquals(12.5, $first->points);
    }

    public function test_assigns_points_for_qualifying(): void
    {
        $race = Race::factory()->create();

        $pointScheme = ChampionshipPointScheme::factory()->create([
            'championship_id' => $race->championship_id,
        ]);

        $runResult = RunResult::factory()->create([
            'race_id' => $race->getKey(),
            'run_type' => RunType::QUALIFY,
        ]);

        $participants = [];
        for ($i = 1; $i <= 5; $i++) {
            $participants[] = ParticipantResult::factory()->create([
                'run_result_id' => $runResult->getKey(),
                'position_in_category' => (string) $i,
                'category' => 'Senior',
                'status' => ResultStatus::FINISHED,
            ]);
        }

        $action = new AssignPointsToRunResult;
        $action($runResult, $pointScheme);

        $participants[0]->refresh();
        $participants[1]->refresh();
        $participants[2]->refresh();

        // Qualify positions: [3, 2, 1]
        $this->assertEquals(3, $participants[0]->points);
        $this->assertEquals(2, $participants[1]->points);
        $this->assertEquals(1, $participants[2]->points);
    }

    public function test_position_beyond_scheme_gets_zero_points(): void
    {
        $race = Race::factory()->create();

        $pointScheme = ChampionshipPointScheme::factory()->create([
            'championship_id' => $race->championship_id,
        ]);

        $runResult = RunResult::factory()->create([
            'race_id' => $race->getKey(),
            'run_type' => RunType::RACE_1,
        ]);

        $participants = [];
        for ($i = 1; $i <= 15; $i++) {
            $participants[] = ParticipantResult::factory()->create([
                'run_result_id' => $runResult->getKey(),
                'position_in_category' => (string) $i,
                'category' => 'Senior',
                'status' => ResultStatus::FINISHED,
            ]);
        }

        $action = new AssignPointsToRunResult;
        $action($runResult, $pointScheme);

        // Position 11 (index 10) is beyond the 10-position scheme
        $participants[10]->refresh();
        $this->assertEquals(0, $participants[10]->points);
    }
}

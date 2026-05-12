<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\AssignPointsToRunResult;
use App\Data\PointsConfigData;
use App\Data\WildcardPointsMode;
use App\Models\ChampionshipPointScheme;
use App\Models\Participant;
use App\Models\ParticipantResult;
use App\Models\Race;
use App\Models\ResultStatus;
use App\Models\RunResult;
use App\Models\RunType;
use Illuminate\Database\Eloquent\Factories\Sequence;
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

    public function test_applies_red_flag_modifier(): void
    {
        $race = Race::factory()->create([
            'red_flag' => true,
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

    public function test_do_not_apply_small_category_modifier(): void
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

        $third = ParticipantResult::factory()->create([
            'run_result_id' => $runResult->getKey(),
            'position_in_category' => '3',
            'category' => 'SmallCat',
            'status' => ResultStatus::FINISHED,
        ]);

        $action = new AssignPointsToRunResult;
        $action($runResult, $pointScheme);

        $first->refresh();
        $this->assertEquals(25, $first->points);
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

    public function test_wildcard_as_other_drivers_assigns_same_points(): void
    {
        $race = Race::factory()->create();

        $pointScheme = ChampionshipPointScheme::factory()->create([
            'championship_id' => $race->championship_id,
        ]);

        $runResult = RunResult::factory()->create([
            'race_id' => $race->getKey(),
            'run_type' => RunType::RACE_1,
        ]);

        $wildcard = Participant::factory()->create([
            'race_id' => $race->getKey(),
            'championship_id' => $race->championship_id,
            'wildcard' => true,
        ]);

        $wildcardResult = ParticipantResult::factory()->forParticipant($wildcard)->create([
            'run_result_id' => $runResult->getKey(),
            'position_in_category' => '1',
            'category' => 'Senior',
            'status' => ResultStatus::FINISHED,
        ]);

        // 4 more non-wildcard results to avoid small category modifier
        for ($i = 2; $i <= 5; $i++) {
            ParticipantResult::factory()->create([
                'run_result_id' => $runResult->getKey(),
                'position_in_category' => (string) $i,
                'category' => 'Senior',
                'status' => ResultStatus::FINISHED,
            ]);
        }

        (new AssignPointsToRunResult)($runResult, $pointScheme);

        $wildcardResult->refresh();
        $this->assertEquals(25, $wildcardResult->points);
    }

    public function test_wildcard_fixed_points_mode_assigns_fixed_points(): void
    {
        $race = Race::factory()->create();

        $baseConfig = ChampionshipPointScheme::factory()->make()->points_config;
        $config = new PointsConfigData(
            rainPercentage: $baseConfig->rainPercentage,
            redFlagPercentage: $baseConfig->redFlagPercentage,
            smallCategoryPercentage: $baseConfig->smallCategoryPercentage,
            smallCategoryThreshold: $baseConfig->smallCategoryThreshold,
            wildcardPointsMode: WildcardPointsMode::FixedPoints,
            wildcardFixedPoints: 5.0,
            runTypes: $baseConfig->runTypes,
        );

        $pointScheme = ChampionshipPointScheme::factory()->create([
            'championship_id' => $race->championship_id,
            'points_config' => $config,
        ]);

        $runResult = RunResult::factory()->create([
            'race_id' => $race->getKey(),
            'run_type' => RunType::RACE_1,
        ]);

        $wildcard = Participant::factory()->create([
            'race_id' => $race->getKey(),
            'championship_id' => $race->championship_id,
            'wildcard' => true,
        ]);

        $wildcardResult = ParticipantResult::factory()->forParticipant($wildcard)->create([
            'run_result_id' => $runResult->getKey(),
            'position_in_category' => '1',
            'category' => 'Senior',
            'status' => ResultStatus::FINISHED,
        ]);

        // 4 more non-wildcard results to avoid small category modifier
        $nonWildcard = ParticipantResult::factory()->create([
            'run_result_id' => $runResult->getKey(),
            'position_in_category' => '2',
            'category' => 'Senior',
            'status' => ResultStatus::FINISHED,
        ]);

        for ($i = 3; $i <= 5; $i++) {
            ParticipantResult::factory()->create([
                'run_result_id' => $runResult->getKey(),
                'position_in_category' => (string) $i,
                'category' => 'Senior',
                'status' => ResultStatus::FINISHED,
            ]);
        }

        (new AssignPointsToRunResult)($runResult, $pointScheme);

        $wildcardResult->refresh();
        $nonWildcard->refresh();

        // Wildcard at position 1 gets fixed points; non-wildcard at position 2 is re-ranked
        // to 1st among non-wildcards → 25 pts
        $this->assertEquals(5.0, $wildcardResult->points);
        $this->assertEquals(25, $nonWildcard->points);
    }

    public function test_wildcard_fixed_points_mode_reranks_non_wildcards_from_first(): void
    {
        $race = Race::factory()->create();

        $baseConfig = ChampionshipPointScheme::factory()->make()->points_config;
        $config = new PointsConfigData(
            rainPercentage: $baseConfig->rainPercentage,
            redFlagPercentage: $baseConfig->redFlagPercentage,
            smallCategoryPercentage: $baseConfig->smallCategoryPercentage,
            smallCategoryThreshold: 1,
            wildcardPointsMode: WildcardPointsMode::FixedPoints,
            wildcardFixedPoints: 5.0,
            runTypes: $baseConfig->runTypes,
        );

        $pointScheme = ChampionshipPointScheme::factory()->create([
            'championship_id' => $race->championship_id,
            'points_config' => $config,
        ]);

        $runResult = RunResult::factory()->create([
            'race_id' => $race->getKey(),
            'run_type' => RunType::RACE_1,
        ]);

        $wildcard = Participant::factory()->create([
            'race_id' => $race->getKey(),
            'championship_id' => $race->championship_id,
            'wildcard' => true,
        ]);

        // Wildcard finishes 1st, non-wildcards fill 2nd, 3rd, 4th
        $wildcardResult = ParticipantResult::factory()->forParticipant($wildcard)->create([
            'run_result_id' => $runResult->getKey(),
            'position_in_category' => '1',
            'category' => 'Senior',
            'status' => ResultStatus::FINISHED,
        ]);

        $nonWildcardResults = ParticipantResult::factory()
            ->count(3)
            ->state(new Sequence(
                ['run_result_id' => $runResult->getKey(), 'position_in_category' => '2', 'category' => 'Senior', 'status' => ResultStatus::FINISHED],
                ['run_result_id' => $runResult->getKey(), 'position_in_category' => '3', 'category' => 'Senior', 'status' => ResultStatus::FINISHED],
                ['run_result_id' => $runResult->getKey(), 'position_in_category' => '4', 'category' => 'Senior', 'status' => ResultStatus::FINISHED],
            ))
            ->create();

        (new AssignPointsToRunResult)($runResult, $pointScheme);

        $wildcardResult->refresh();
        $nonWildcardResults->each->refresh();

        $this->assertEquals(5.0, $wildcardResult->points);
        // Non-wildcards re-ranked: 2nd overall → 1st among non-wildcards → 25, then 18, 15
        $this->assertEquals([25, 18, 15], $nonWildcardResults->map->points->all());
    }

    public function test_wildcard_fixed_points_mode_does_not_affect_unfinished_wildcards(): void
    {
        $race = Race::factory()->create();

        $baseConfig = ChampionshipPointScheme::factory()->make()->points_config;
        $config = new PointsConfigData(
            rainPercentage: $baseConfig->rainPercentage,
            redFlagPercentage: $baseConfig->redFlagPercentage,
            smallCategoryPercentage: $baseConfig->smallCategoryPercentage,
            smallCategoryThreshold: $baseConfig->smallCategoryThreshold,
            wildcardPointsMode: WildcardPointsMode::FixedPoints,
            wildcardFixedPoints: 5.0,
            runTypes: $baseConfig->runTypes,
        );

        $pointScheme = ChampionshipPointScheme::factory()->create([
            'championship_id' => $race->championship_id,
            'points_config' => $config,
        ]);

        $runResult = RunResult::factory()->create([
            'race_id' => $race->getKey(),
            'run_type' => RunType::RACE_1,
        ]);

        $wildcard = Participant::factory()->create([
            'race_id' => $race->getKey(),
            'championship_id' => $race->championship_id,
            'wildcard' => true,
        ]);

        $wildcardDnf = ParticipantResult::factory()->forParticipant($wildcard)->create([
            'run_result_id' => $runResult->getKey(),
            'position_in_category' => '5',
            'category' => 'Senior',
            'status' => ResultStatus::DID_NOT_FINISH,
            'is_dnf' => true,
        ]);

        (new AssignPointsToRunResult)($runResult, $pointScheme);

        $wildcardDnf->refresh();
        $this->assertEquals(0, $wildcardDnf->points);
    }

    public function test_wildcard_ranked_from_first_assigns_points_by_wildcard_rank(): void
    {
        $race = Race::factory()->create();

        $baseConfig = ChampionshipPointScheme::factory()->make()->points_config;
        $config = new PointsConfigData(
            rainPercentage: $baseConfig->rainPercentage,
            redFlagPercentage: $baseConfig->redFlagPercentage,
            smallCategoryPercentage: $baseConfig->smallCategoryPercentage,
            smallCategoryThreshold: 1,
            wildcardPointsMode: WildcardPointsMode::RankedFromFirst,
            runTypes: $baseConfig->runTypes,
        );

        $pointScheme = ChampionshipPointScheme::factory()->create([
            'championship_id' => $race->championship_id,
            'points_config' => $config,
        ]);

        $runResult = RunResult::factory()->create([
            'race_id' => $race->getKey(),
            'run_type' => RunType::RACE_1,
        ]);

        $wildcard1 = Participant::factory()->create([
            'race_id' => $race->getKey(),
            'championship_id' => $race->championship_id,
            'wildcard' => true,
        ]);

        $wildcard2 = Participant::factory()->create([
            'race_id' => $race->getKey(),
            'championship_id' => $race->championship_id,
            'wildcard' => true,
        ]);

        // Wildcards finish 3rd and 5th overall in category
        $wildcardResult1 = ParticipantResult::factory()->forParticipant($wildcard1)->create([
            'run_result_id' => $runResult->getKey(),
            'position_in_category' => '3',
            'category' => 'Senior',
            'status' => ResultStatus::FINISHED,
        ]);

        $wildcardResult2 = ParticipantResult::factory()->forParticipant($wildcard2)->create([
            'run_result_id' => $runResult->getKey(),
            'position_in_category' => '5',
            'category' => 'Senior',
            'status' => ResultStatus::FINISHED,
        ]);

        $nonWildcardParticipantResults = ParticipantResult::factory()
            ->count(4)
            ->state(new Sequence(
                [
                    'run_result_id' => $runResult->getKey(),
                    'position_in_category' => '1',
                    'category' => 'Senior',
                    'status' => ResultStatus::FINISHED, ],
                [
                    'run_result_id' => $runResult->getKey(),
                    'position_in_category' => '2',
                    'category' => 'Senior',
                    'status' => ResultStatus::FINISHED, ],
                [
                    'run_result_id' => $runResult->getKey(),
                    'position_in_category' => '4',
                    'category' => 'Senior',
                    'status' => ResultStatus::FINISHED, ],
                [
                    'run_result_id' => $runResult->getKey(),
                    'position_in_category' => '6',
                    'category' => 'Senior',
                    'status' => ResultStatus::FINISHED, ],
            ))
            ->create();

        (new AssignPointsToRunResult)($runResult, $pointScheme);

        $wildcardResult1->refresh();
        $wildcardResult2->refresh();

        $nonWildcardParticipantResults->each->refresh();

        // First wildcard (3rd overall) → ranked 1st among wildcards → 25 pts
        $this->assertEquals(25, $wildcardResult1->points);
        // Second wildcard (5th overall) → ranked 2nd among wildcards → 18 pts
        $this->assertEquals(18, $wildcardResult2->points);

        // The others gain points based on their ranking excluding the wildcards
        $this->assertEquals([25, 18, 15, 12], $nonWildcardParticipantResults->map->points->all());

    }

    public function test_wildcard_ranked_from_first_non_wildcards_unaffected(): void
    {
        $race = Race::factory()->create();

        $baseConfig = ChampionshipPointScheme::factory()->make()->points_config;
        $config = new PointsConfigData(
            rainPercentage: $baseConfig->rainPercentage,
            redFlagPercentage: $baseConfig->redFlagPercentage,
            smallCategoryPercentage: $baseConfig->smallCategoryPercentage,
            smallCategoryThreshold: $baseConfig->smallCategoryThreshold,
            wildcardPointsMode: WildcardPointsMode::RankedFromFirst,
            runTypes: $baseConfig->runTypes,
        );

        $pointScheme = ChampionshipPointScheme::factory()->create([
            'championship_id' => $race->championship_id,
            'points_config' => $config,
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

        (new AssignPointsToRunResult)($runResult, $pointScheme);

        $participants[0]->refresh();
        $participants[1]->refresh();
        $this->assertEquals(25, $participants[0]->points);
        $this->assertEquals(18, $participants[1]->points);
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

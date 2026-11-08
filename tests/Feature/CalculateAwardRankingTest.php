<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\CalculateAwardRanking;
use App\Models\Category;
use App\Models\Championship;
use App\Models\ChampionshipAward;
use App\Models\Participant;
use App\Models\ParticipantResult;
use App\Models\Race;
use App\Models\ResultStatus;
use App\Models\RunResult;
use App\Models\RunType;
use Illuminate\Support\Arr;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class CalculateAwardRankingTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_category_award_ranks_participants_by_total_points(): void
    {
        $championship = Championship::factory()->create();
        $category = Category::factory()->create(['championship_id' => $championship->getKey()]);
        $race = Race::factory()->create(['championship_id' => $championship->getKey()]);
        $runResult = RunResult::factory()->published()->create(['race_id' => $race->getKey()]);

        $participant1 = Participant::factory()->create([
            'championship_id' => $championship->getKey(),
            'race_id' => $race->getKey(),
            'bib' => 10,
            'first_name' => 'Alice',
            'last_name' => 'Smith',
        ]);

        $participant2 = Participant::factory()->create([
            'championship_id' => $championship->getKey(),
            'race_id' => $race->getKey(),
            'bib' => 20,
            'first_name' => 'Bob',
            'last_name' => 'Jones',
        ]);

        ParticipantResult::factory()->create([
            'run_result_id' => $runResult->getKey(),
            'participant_id' => $participant1->getKey(),
            'category_id' => $category->getKey(),
            'points' => 25,
        ]);

        ParticipantResult::factory()->create([
            'run_result_id' => $runResult->getKey(),
            'participant_id' => $participant2->getKey(),
            'category_id' => $category->getKey(),
            'points' => 18,
        ]);

        $award = ChampionshipAward::factory()->create([
            'championship_id' => $championship->getKey(),
            'category_id' => $category->getKey(),
        ]);

        $ranking = app(CalculateAwardRanking::class)($award);

        $this->assertCount(2, $ranking);
        $this->assertEquals('Alice', $ranking[0]['first_name']);
        $this->assertEquals(25.0, $ranking[0]['total_points']);
        $this->assertArrayHasKey('points_per_race', $ranking[0]);
        $this->assertEquals(25.0, $ranking[0]['points_per_race'][$race->getKey()]);
        $this->assertEquals('Bob', $ranking[1]['first_name']);
        $this->assertEquals(18.0, $ranking[1]['total_points']);
        $this->assertEquals(18.0, $ranking[1]['points_per_race'][$race->getKey()]);
    }

    public function test_category_award_sums_points_across_multiple_races(): void
    {
        $championship = Championship::factory()->create();
        $category = Category::factory()->create(['championship_id' => $championship->getKey()]);

        $race1 = Race::factory()->create(['championship_id' => $championship->getKey()]);
        $race2 = Race::factory()->create(['championship_id' => $championship->getKey()]);
        $runResult1 = RunResult::factory()->published()->create(['race_id' => $race1->getKey()]);
        $runResult2 = RunResult::factory()->published()->create(['race_id' => $race2->getKey()]);

        $participant = Participant::factory()->create([
            'championship_id' => $championship->getKey(),
            'race_id' => $race1->getKey(),
            'bib' => 10,
        ]);

        ParticipantResult::factory()->create([
            'run_result_id' => $runResult1->getKey(),
            'participant_id' => $participant->getKey(),
            'category_id' => $category->getKey(),
            'points' => 25,
        ]);

        ParticipantResult::factory()->create([
            'run_result_id' => $runResult2->getKey(),
            'participant_id' => $participant->getKey(),
            'category_id' => $category->getKey(),
            'points' => 18,
        ]);

        $award = ChampionshipAward::factory()->create([
            'championship_id' => $championship->getKey(),
            'category_id' => $category->getKey(),
        ]);

        $ranking = app(CalculateAwardRanking::class)($award);

        $this->assertCount(1, $ranking);
        $this->assertEquals(43.0, $ranking[0]['total_points']);
        $this->assertEquals(2, $ranking[0]['races_counted']);
        $this->assertEquals(25.0, $ranking[0]['points_per_race'][$race1->getKey()]);
        $this->assertEquals(18.0, $ranking[0]['points_per_race'][$race2->getKey()]);
    }

    public function test_category_award_groups_by_racer_hash_across_races(): void
    {
        $championship = Championship::factory()->create();
        $category = Category::factory()->recycle($championship)->create();

        $race1 = Race::factory()->recycle($championship)->create();
        $race2 = Race::factory()->recycle($championship)->create();
        $runResult1 = RunResult::factory()->published()->recycle($race1)->create();
        $runResult2 = RunResult::factory()->published()->recycle($race2)->create();

        $racerHash = 'ABCD1234';

        $participant1 = Participant::factory()
            ->recycle($championship)
            ->recycle($race1)
            ->category($category)
            ->create([
                'bib' => 10,
                'racer_hash' => $racerHash,
            ]);

        $participant2 = Participant::factory()
            ->recycle($championship)
            ->recycle($race2)
            ->category($category)
            ->create([
                'bib' => 10,
                'racer_hash' => $racerHash,
            ]);

        ParticipantResult::factory()
            ->recycle($runResult1)
            ->forParticipant($participant1)->create([
                'points' => 25,
            ]);

        ParticipantResult::factory()
            ->recycle($runResult2)
            ->forParticipant($participant2)
            ->create([
                'points' => 18,
            ]);

        $award = ChampionshipAward::factory()->create([
            'championship_id' => $championship->getKey(),
            'category_id' => $category->getKey(),
        ]);

        $ranking = app(CalculateAwardRanking::class)($award);

        $this->assertCount(1, $ranking);
        $this->assertEquals(43.0, $ranking[0]['total_points']);
        $this->assertEquals(2, $ranking[0]['races_counted']);
        $this->assertEquals(25.0, $ranking[0]['points_per_race'][$race1->getKey()]);
        $this->assertEquals(18.0, $ranking[0]['points_per_race'][$race2->getKey()]);
    }

    public function test_best_n_ranking_mode_takes_only_best_races(): void
    {
        $championship = Championship::factory()->create();
        $category = Category::factory()->create(['championship_id' => $championship->getKey()]);

        $races = [];
        $runResults = [];
        for ($i = 0; $i < 3; $i++) {
            $races[$i] = Race::factory()->create(['championship_id' => $championship->getKey()]);
            $runResults[$i] = RunResult::factory()->published()->create(['race_id' => $races[$i]->getKey()]);
        }

        $participant = Participant::factory()->create([
            'championship_id' => $championship->getKey(),
            'race_id' => $races[0]->getKey(),
            'bib' => 10,
        ]);

        ParticipantResult::factory()->create([
            'run_result_id' => $runResults[0]->getKey(),
            'participant_id' => $participant->getKey(),
            'category_id' => $category->getKey(),
            'points' => 25,
        ]);

        ParticipantResult::factory()->create([
            'run_result_id' => $runResults[1]->getKey(),
            'participant_id' => $participant->getKey(),
            'category_id' => $category->getKey(),
            'points' => 10,
        ]);

        ParticipantResult::factory()->create([
            'run_result_id' => $runResults[2]->getKey(),
            'participant_id' => $participant->getKey(),
            'category_id' => $category->getKey(),
            'points' => 18,
        ]);

        $award = ChampionshipAward::factory()->bestN(2)->create([
            'championship_id' => $championship->getKey(),
            'category_id' => $category->getKey(),
        ]);

        $ranking = app(CalculateAwardRanking::class)($award);

        $this->assertCount(1, $ranking);
        $this->assertEquals(43.0, $ranking[0]['total_points']); // 25 + 18, skip 10
        $this->assertEquals(2, $ranking[0]['races_counted']);
        $this->assertArrayHasKey('points_per_race', $ranking[0]);
        $this->assertCount(3, $ranking[0]['points_per_race']); // all races present
        $this->assertArrayHasKey('counted_race_ids', $ranking[0]);
        $this->assertCount(2, $ranking[0]['counted_race_ids']); // only best 2
        $this->assertContains($races[0]->getKey(), $ranking[0]['counted_race_ids']);
        $this->assertContains($races[2]->getKey(), $ranking[0]['counted_race_ids']);
        $this->assertNotContains($races[1]->getKey(), $ranking[0]['counted_race_ids']);
    }

    public function test_specific_races_mode_filters_to_selected_races(): void
    {
        $championship = Championship::factory()->create();
        $category = Category::factory()->create(['championship_id' => $championship->getKey()]);

        $race1 = Race::factory()->create(['championship_id' => $championship->getKey()]);
        $race2 = Race::factory()->create(['championship_id' => $championship->getKey()]);
        $race3 = Race::factory()->create(['championship_id' => $championship->getKey()]);

        $runResult1 = RunResult::factory()->published()->create(['race_id' => $race1->getKey()]);
        $runResult2 = RunResult::factory()->published()->create(['race_id' => $race2->getKey()]);
        $runResult3 = RunResult::factory()->published()->create(['race_id' => $race3->getKey()]);

        $participant = Participant::factory()->create([
            'championship_id' => $championship->getKey(),
            'race_id' => $race1->getKey(),
            'bib' => 10,
        ]);

        ParticipantResult::factory()->create([
            'run_result_id' => $runResult1->getKey(),
            'participant_id' => $participant->getKey(),
            'category_id' => $category->getKey(),
            'points' => 25,
        ]);

        ParticipantResult::factory()->create([
            'run_result_id' => $runResult2->getKey(),
            'participant_id' => $participant->getKey(),
            'category_id' => $category->getKey(),
            'points' => 18,
        ]);

        ParticipantResult::factory()->create([
            'run_result_id' => $runResult3->getKey(),
            'participant_id' => $participant->getKey(),
            'category_id' => $category->getKey(),
            'points' => 10,
        ]);

        $award = ChampionshipAward::factory()->specificRaces()->create([
            'championship_id' => $championship->getKey(),
            'category_id' => $category->getKey(),
        ]);

        $award->races()->sync([$race1->getKey(), $race2->getKey()]);

        $ranking = app(CalculateAwardRanking::class)($award);

        $this->assertCount(1, $ranking);
        $this->assertEquals(43.0, $ranking[0]['total_points']); // 25 + 18, race3 excluded
    }

    public function test_wildcard_filter_excludes_wildcards(): void
    {
        $championship = Championship::factory()->create();
        $category = Category::factory()->create(['championship_id' => $championship->getKey()]);
        $race = Race::factory()->create(['championship_id' => $championship->getKey()]);
        $runResult = RunResult::factory()->published()->create(['race_id' => $race->getKey()]);

        $regular = Participant::factory()->create([
            'championship_id' => $championship->getKey(),
            'race_id' => $race->getKey(),
            'bib' => 10,
            'wildcard' => false,
        ]);

        $wildcard = Participant::factory()->create([
            'championship_id' => $championship->getKey(),
            'race_id' => $race->getKey(),
            'bib' => 20,
            'wildcard' => true,
        ]);

        ParticipantResult::factory()->create([
            'run_result_id' => $runResult->getKey(),
            'participant_id' => $regular->getKey(),
            'category_id' => $category->getKey(),
            'points' => 25,
        ]);

        ParticipantResult::factory()->create([
            'run_result_id' => $runResult->getKey(),
            'participant_id' => $wildcard->getKey(),
            'category_id' => $category->getKey(),
            'points' => 18,
        ]);

        $award = ChampionshipAward::factory()->excludeWildcards()->create([
            'championship_id' => $championship->getKey(),
            'category_id' => $category->getKey(),
        ]);

        $ranking = app(CalculateAwardRanking::class)($award);

        $this->assertCount(1, $ranking);
        $this->assertEquals(10, $ranking[0]['bib']);
    }

    public function test_wildcard_filter_only_wildcards(): void
    {
        $championship = Championship::factory()->create();
        $category = Category::factory()->create(['championship_id' => $championship->getKey()]);
        $race = Race::factory()->create(['championship_id' => $championship->getKey()]);
        $runResult = RunResult::factory()->published()->create(['race_id' => $race->getKey()]);

        $regular = Participant::factory()->create([
            'championship_id' => $championship->getKey(),
            'race_id' => $race->getKey(),
            'bib' => 10,
            'wildcard' => false,
        ]);

        $wildcard = Participant::factory()->create([
            'championship_id' => $championship->getKey(),
            'race_id' => $race->getKey(),
            'bib' => 20,
            'wildcard' => true,
        ]);

        ParticipantResult::factory()->create([
            'run_result_id' => $runResult->getKey(),
            'participant_id' => $regular->getKey(),
            'category_id' => $category->getKey(),
            'points' => 25,
        ]);

        ParticipantResult::factory()->create([
            'run_result_id' => $runResult->getKey(),
            'participant_id' => $wildcard->getKey(),
            'category_id' => $category->getKey(),
            'points' => 18,
        ]);

        $award = ChampionshipAward::factory()->onlyWildcards()->create([
            'championship_id' => $championship->getKey(),
            'category_id' => $category->getKey(),
        ]);

        $ranking = app(CalculateAwardRanking::class)($award);

        $this->assertCount(1, $ranking);
        $this->assertEquals(20, $ranking[0]['bib']);
    }

    public function test_overall_award_combines_multiple_categories(): void
    {
        $championship = Championship::factory()->create();
        $cat1 = Category::factory()->create(['championship_id' => $championship->getKey()]);
        $cat2 = Category::factory()->create(['championship_id' => $championship->getKey()]);
        $race = Race::factory()->create(['championship_id' => $championship->getKey()]);
        $runResult = RunResult::factory()->published()->create(['race_id' => $race->getKey()]);

        $participant = Participant::factory()->create([
            'championship_id' => $championship->getKey(),
            'race_id' => $race->getKey(),
            'bib' => 10,
        ]);

        ParticipantResult::factory()->create([
            'run_result_id' => $runResult->getKey(),
            'participant_id' => $participant->getKey(),
            'category_id' => $cat1->getKey(),
            'points' => 25,
        ]);

        ParticipantResult::factory()->create([
            'run_result_id' => $runResult->getKey(),
            'participant_id' => $participant->getKey(),
            'category_id' => $cat2->getKey(),
            'points' => 10,
        ]);

        $award = ChampionshipAward::factory()->overallAward()->create([
            'championship_id' => $championship->getKey(),
        ]);

        $award->categories()->sync([$cat1->getKey(), $cat2->getKey()]);

        $ranking = app(CalculateAwardRanking::class)($award);

        $this->assertCount(1, $ranking);
        $this->assertEquals(35.0, $ranking[0]['total_points']);
    }

    public function test_overall_award_specific_races_mode_filters_to_selected_races(): void
    {
        $championship = Championship::factory()->create();
        $cat1 = Category::factory()->create(['championship_id' => $championship->getKey()]);
        $cat2 = Category::factory()->create(['championship_id' => $championship->getKey()]);

        $race1 = Race::factory()->create(['championship_id' => $championship->getKey()]);
        $race2 = Race::factory()->create(['championship_id' => $championship->getKey()]);

        $runResult1 = RunResult::factory()->published()->create(['race_id' => $race1->getKey()]);
        $runResult2 = RunResult::factory()->published()->create(['race_id' => $race2->getKey()]);

        $participant = Participant::factory()->create([
            'championship_id' => $championship->getKey(),
            'race_id' => $race1->getKey(),
            'bib' => 10,
        ]);

        ParticipantResult::factory()->create([
            'run_result_id' => $runResult1->getKey(),
            'participant_id' => $participant->getKey(),
            'category_id' => $cat1->getKey(),
            'points' => 25,
        ]);

        ParticipantResult::factory()->create([
            'run_result_id' => $runResult2->getKey(),
            'participant_id' => $participant->getKey(),
            'category_id' => $cat2->getKey(),
            'points' => 18,
        ]);

        $award = ChampionshipAward::factory()->overallAward()->specificRaces()->create([
            'championship_id' => $championship->getKey(),
        ]);

        $award->categories()->sync([$cat1->getKey(), $cat2->getKey()]);
        $award->races()->sync([$race1->getKey()]);

        $ranking = app(CalculateAwardRanking::class)($award);

        $this->assertCount(1, $ranking);
        $this->assertEquals(25.0, $ranking[0]['total_points']); // race2 excluded
    }

    public function test_unpublished_run_results_included_when_published_only_false(): void
    {
        $championship = Championship::factory()->create();
        $category = Category::factory()->create(['championship_id' => $championship->getKey()]);
        $race = Race::factory()->create(['championship_id' => $championship->getKey()]);
        $runResult = RunResult::factory()->create([
            'race_id' => $race->getKey(),
            'published_at' => null,
        ]);

        $participant = Participant::factory()->create([
            'championship_id' => $championship->getKey(),
            'race_id' => $race->getKey(),
        ]);

        ParticipantResult::factory()->create([
            'run_result_id' => $runResult->getKey(),
            'participant_id' => $participant->getKey(),
            'category_id' => $category->getKey(),
            'points' => 25,
        ]);

        $award = ChampionshipAward::factory()->create([
            'championship_id' => $championship->getKey(),
            'category_id' => $category->getKey(),
        ]);

        $ranking = app(CalculateAwardRanking::class)($award, publishedOnly: false);

        $this->assertCount(1, $ranking);
        $this->assertEquals(25.0, $ranking[0]['total_points']);
    }

    public function test_unpublished_run_results_are_excluded(): void
    {
        $championship = Championship::factory()->create();
        $category = Category::factory()->create(['championship_id' => $championship->getKey()]);
        $race = Race::factory()->create(['championship_id' => $championship->getKey()]);
        $runResult = RunResult::factory()->create([
            'race_id' => $race->getKey(),
            'published_at' => null,
        ]);

        $participant = Participant::factory()->create([
            'championship_id' => $championship->getKey(),
            'race_id' => $race->getKey(),
        ]);

        ParticipantResult::factory()->create([
            'run_result_id' => $runResult->getKey(),
            'participant_id' => $participant->getKey(),
            'category_id' => $category->getKey(),
            'points' => 25,
        ]);

        $award = ChampionshipAward::factory()->create([
            'championship_id' => $championship->getKey(),
            'category_id' => $category->getKey(),
        ]);

        $ranking = app(CalculateAwardRanking::class)($award);

        $this->assertCount(0, $ranking);
    }

    public function test_results_without_participant_are_excluded(): void
    {
        $championship = Championship::factory()->create();
        $category = Category::factory()->create(['championship_id' => $championship->getKey()]);
        $race = Race::factory()->create(['championship_id' => $championship->getKey()]);
        $runResult = RunResult::factory()->published()->create(['race_id' => $race->getKey()]);

        ParticipantResult::factory()->create([
            'run_result_id' => $runResult->getKey(),
            'participant_id' => null,
            'category_id' => $category->getKey(),
            'points' => 25,
        ]);

        $award = ChampionshipAward::factory()->create([
            'championship_id' => $championship->getKey(),
            'category_id' => $category->getKey(),
        ]);

        $ranking = app(CalculateAwardRanking::class)($award);

        $this->assertCount(0, $ranking);
    }

    public function test_results_from_other_championships_are_excluded(): void
    {
        $championship = Championship::factory()->create();
        $otherChampionship = Championship::factory()->create();
        $category = Category::factory()->create(['championship_id' => $championship->getKey()]);

        $otherRace = Race::factory()->create(['championship_id' => $otherChampionship->getKey()]);
        $otherRunResult = RunResult::factory()->published()->create(['race_id' => $otherRace->getKey()]);

        $participant = Participant::factory()->create([
            'championship_id' => $otherChampionship->getKey(),
            'race_id' => $otherRace->getKey(),
            'bib' => 10,
        ]);

        ParticipantResult::factory()->create([
            'run_result_id' => $otherRunResult->getKey(),
            'participant_id' => $participant->getKey(),
            'category_id' => $category->getKey(),
            'points' => 25,
        ]);

        $award = ChampionshipAward::factory()->create([
            'championship_id' => $championship->getKey(),
            'category_id' => $category->getKey(),
        ]);

        $ranking = app(CalculateAwardRanking::class)($award);

        $this->assertCount(0, $ranking);
    }

    public function test_breakdown_lists_every_run_result_that_contributed_to_the_total(): void
    {
        $championship = Championship::factory()->create();
        $category = Category::factory()->create(['championship_id' => $championship->getKey()]);
        $race = Race::factory()->create(['championship_id' => $championship->getKey()]);

        $qualifying = RunResult::factory()->published()->create([
            'race_id' => $race->getKey(),
            'run_type' => RunType::QUALIFY->value,
            'title' => 'Qualifying',
        ]);
        $finalRun = RunResult::factory()->published()->create([
            'race_id' => $race->getKey(),
            'run_type' => RunType::RACE_2->value,
            'title' => 'Final',
        ]);

        $participant = Participant::factory()->create([
            'championship_id' => $championship->getKey(),
            'race_id' => $race->getKey(),
            'bib' => 10,
        ]);

        ParticipantResult::factory()->forParticipant($participant)->create([
            'run_result_id' => $qualifying->getKey(),
            'category_id' => $category->getKey(),
            'points' => 5,
            'position' => '2',
        ]);

        ParticipantResult::factory()->forParticipant($participant)->create([
            'run_result_id' => $finalRun->getKey(),
            'category_id' => $category->getKey(),
            'points' => 20,
            'position' => '1',
        ]);

        $award = ChampionshipAward::factory()->create([
            'championship_id' => $championship->getKey(),
            'category_id' => $category->getKey(),
        ]);

        $breakdown = app(CalculateAwardRanking::class)->breakdown($award, $participant->racer_hash);

        $this->assertEquals(25.0, $breakdown['total_points']);
        $this->assertEquals(25.0, $breakdown['points_per_race'][$race->getKey()]);

        $entries = $breakdown['entries_by_race']->get($race->getKey());
        $this->assertCount(2, $entries);
        $this->assertEqualsCanonicalizing(
            ['Qualifying', 'Final'],
            $entries->pluck('run_title')->all(),
        );
        $this->assertEqualsCanonicalizing([5.0, 20.0], $entries->pluck('points')->all());
    }

    public function test_breakdown_marks_races_excluded_by_best_n(): void
    {
        $championship = Championship::factory()->create();
        $category = Category::factory()->create(['championship_id' => $championship->getKey()]);

        $race1 = Race::factory()->create(['championship_id' => $championship->getKey()]);
        $race2 = Race::factory()->create(['championship_id' => $championship->getKey()]);

        $runResult1 = RunResult::factory()->published()->create(['race_id' => $race1->getKey()]);
        $runResult2 = RunResult::factory()->published()->create(['race_id' => $race2->getKey()]);

        $participant = Participant::factory()->create([
            'championship_id' => $championship->getKey(),
            'race_id' => $race1->getKey(),
            'bib' => 10,
        ]);

        ParticipantResult::factory()->forParticipant($participant)->create([
            'run_result_id' => $runResult1->getKey(),
            'category_id' => $category->getKey(),
            'points' => 25,
        ]);

        ParticipantResult::factory()->forParticipant($participant)->create([
            'run_result_id' => $runResult2->getKey(),
            'category_id' => $category->getKey(),
            'points' => 10,
        ]);

        $award = ChampionshipAward::factory()->bestN(1)->create([
            'championship_id' => $championship->getKey(),
            'category_id' => $category->getKey(),
        ]);

        $breakdown = app(CalculateAwardRanking::class)->breakdown($award, $participant->racer_hash);

        $this->assertEquals(25.0, $breakdown['total_points']);
        $this->assertContains($race1->getKey(), $breakdown['counted_race_ids']);
        $this->assertNotContains($race2->getKey(), $breakdown['counted_race_ids']);
        $this->assertCount(1, $breakdown['entries_by_race']->get($race2->getKey()));
    }

    public function test_day_award_adds_smallest_session_size_once_to_points(): void
    {
        $championship = Championship::factory()->create();
        $mini = Category::factory()->recycle($championship)->create();
        $miniUnder10 = Category::factory()->recycle($championship)->create();
        $otherCategory = Category::factory()->recycle($championship)->create();
        $race = Race::factory()->recycle($championship)->create();

        $driverA = Participant::factory()->recycle($championship)->recycle($race)->category($mini)->create(['first_name' => 'Alice']);
        $driverB = Participant::factory()->recycle($championship)->recycle($race)->category($mini)->create(['first_name' => 'Bob']);
        $driverC = Participant::factory()->recycle($championship)->recycle($race)->category($miniUnder10)->create(['first_name' => 'Carol']);
        $driverX = Participant::factory()->recycle($championship)->recycle($race)->category($otherCategory)->create();

        $qualify = RunResult::factory()->published()->recycle($race)->create(['run_type' => RunType::QUALIFY]);
        $this->createResult($qualify, $driverA, 10);
        $this->createResult($qualify, $driverB, 0, dns: true);
        $this->createResult($qualify, $driverC, 8);
        $this->createResult($qualify, $driverX, 5);

        $race1Mini = RunResult::factory()->published()->recycle($race)->create(['run_type' => RunType::RACE_1]);
        $this->createResult($race1Mini, $driverA, 20);
        $this->createResult($race1Mini, $driverB, 0, dns: true);

        $race1MiniUnder10 = RunResult::factory()->published()->recycle($race)->create(['run_type' => RunType::RACE_1]);
        $this->createResult($race1MiniUnder10, $driverC, 20);
        ParticipantResult::factory()->count(2)->create(['run_result_id' => $race1MiniUnder10->getKey(), 'participant_id' => null, 'points' => 0]);

        $race2 = RunResult::factory()->published()->recycle($race)->create(['run_type' => RunType::RACE_2]);
        $this->createResult($race2, $driverA, 25);
        $this->createResult($race2, $driverB, 0, dns: true);
        $this->createResult($race2, $driverC, 30);

        $warmUp = RunResult::factory()->published()->recycle($race)->create(['run_type' => RunType::WARM_UP]);
        $this->createResult($warmUp, $driverA, 100);

        $award = ChampionshipAward::factory()->dayAward()->recycle($championship)->create();
        $award->categories()->sync([$mini->getKey(), $miniUnder10->getKey()]);
        $award->races()->sync([$race->getKey()]);

        $ranking = app(CalculateAwardRanking::class)($award);

        $this->assertEquals(['Carol', 'Alice', 'Bob'], $ranking->pluck('first_name')->all());

        $this->assertEquals(3, $ranking[0]['session_bonus']);
        $this->assertEquals(58.0, $ranking[0]['points_per_race'][$race->getKey()]);
        $this->assertEquals(61.0, $ranking[0]['total_points']);

        $this->assertEquals(2, $ranking[1]['session_bonus']);
        $this->assertEquals(55.0, $ranking[1]['points_per_race'][$race->getKey()]);
        $this->assertEquals(57.0, $ranking[1]['total_points']);

        $this->assertEquals(2, $ranking[2]['session_bonus']);
        $this->assertEquals(2.0, $ranking[2]['total_points']);

        $breakdown = app(CalculateAwardRanking::class)->breakdown($award, $driverA->racer_hash);

        $this->assertEquals(57.0, $breakdown['total_points']);
        $this->assertEquals(2, $breakdown['session_bonus']);
        $this->assertCount(3, $breakdown['entries_by_race']->get($race->getKey()));
    }

    public function test_day_award_uses_the_size_of_the_heats_classification_as_session_bonus(): void
    {
        $championship = Championship::factory()->create();
        $category = Category::factory()->recycle($championship)->create();
        $race = Race::factory()->recycle($championship)->create();

        $drivers = Participant::factory()->count(4)->recycle($championship)->recycle($race)->category($category)->create();

        $heats = RunResult::factory()->published()->recycle($race)->create(['run_type' => RunType::RACE_1, 'is_generated' => true]);
        foreach ($drivers as $driver) {
            $this->createResult($heats, $driver, 10);
        }

        $final = RunResult::factory()->published()->recycle($race)->create(['run_type' => RunType::RACE_2]);
        $this->createResult($final, $drivers[0], 20);
        $this->createResult($final, $drivers[1], 18);

        $award = ChampionshipAward::factory()->dayAward()->recycle($championship)->create();
        $award->categories()->sync([$category->getKey()]);
        $award->races()->sync([$race->getKey()]);

        $ranking = app(CalculateAwardRanking::class)($award);

        $this->assertCount(4, $ranking);
        $this->assertEquals([4, 4, 4, 4], $ranking->pluck('session_bonus')->all());
        $this->assertEquals(34.0, $ranking[0]['total_points']);
    }

    public function test_day_award_breaks_ties_by_session_size_then_race_and_qualifying_best_lap(): void
    {
        $championship = Championship::factory()->create();
        $category = Category::factory()->recycle($championship)->create();
        $race = Race::factory()->recycle($championship)->create();

        $participants = collect(['Anna', 'Bruno', 'Carla', 'Dario', 'Elena', 'Fabio'])->mapWithKeys(fn (string $name) => [
            $name => Participant::factory()->recycle($championship)->recycle($race)->category($category)->create(['first_name' => $name]),
        ]);

        $smallQualifying = RunResult::factory()->published()->recycle($race)->create(['run_type' => RunType::QUALIFY]);
        $this->createResult($smallQualifying, $participants['Anna'], 7, bestLapTime: '50.000');
        ParticipantResult::factory()->create(['run_result_id' => $smallQualifying->getKey(), 'participant_id' => null, 'points' => 0]);

        $smallRace1 = RunResult::factory()->published()->recycle($race)->create(['run_type' => RunType::RACE_1]);
        $this->createResult($smallRace1, $participants['Anna'], 0, bestLapTime: '50.000');
        ParticipantResult::factory()->create(['run_result_id' => $smallRace1->getKey(), 'participant_id' => null, 'points' => 0]);

        $smallRace2 = RunResult::factory()->published()->recycle($race)->create(['run_type' => RunType::RACE_2]);
        $this->createResult($smallRace2, $participants['Anna'], 6, bestLapTime: '50.000');
        ParticipantResult::factory()->create(['run_result_id' => $smallRace2->getKey(), 'participant_id' => null, 'points' => 0]);

        $busyQualifying = RunResult::factory()->published()->recycle($race)->create(['run_type' => RunType::QUALIFY]);
        $this->createResult($busyQualifying, $participants['Bruno'], 5, bestLapTime: '55.000');
        $this->createResult($busyQualifying, $participants['Carla'], 5, bestLapTime: '58.000');
        $this->createResult($busyQualifying, $participants['Dario'], 5, bestLapTime: '57.500');
        $this->createResult($busyQualifying, $participants['Elena'], 5, bestLapTime: '55.000');
        $this->createResult($busyQualifying, $participants['Fabio'], 5, bestLapTime: '54.000');

        $busyRace1 = RunResult::factory()->published()->recycle($race)->create(['run_type' => RunType::RACE_1]);
        $this->createResult($busyRace1, $participants['Bruno'], 0, bestLapTime: '1:01.000');
        $this->createResult($busyRace1, $participants['Carla'], 0, bestLapTime: '1:01.000');
        $this->createResult($busyRace1, $participants['Dario'], 0, bestLapTime: '1:01.000');
        $this->createResult($busyRace1, $participants['Elena'], 0, bestLapTime: '1:00.000');
        $this->createResult($busyRace1, $participants['Fabio'], 0, dnf: true, bestLapTime: '58.000');

        $busyRace2 = RunResult::factory()->published()->recycle($race)->create(['run_type' => RunType::RACE_2]);
        $this->createResult($busyRace2, $participants['Bruno'], 5, bestLapTime: '1:00.500');
        $this->createResult($busyRace2, $participants['Carla'], 5, bestLapTime: '59.900');
        $this->createResult($busyRace2, $participants['Dario'], 5, bestLapTime: '59.900');
        $this->createResult($busyRace2, $participants['Elena'], 5, dns: true, bestLapTime: '');
        $this->createResult($busyRace2, $participants['Fabio'], 5, bestLapTime: '59.000');

        $award = ChampionshipAward::factory()->dayAward()->recycle($championship)->create();
        $award->categories()->sync([$category->getKey()]);
        $award->races()->sync([$race->getKey()]);

        $ranking = app(CalculateAwardRanking::class)($award);

        $this->assertEquals([15.0, 15.0, 15.0, 15.0, 15.0, 15.0], $ranking->pluck('total_points')->all());
        $this->assertEquals(['Elena', 'Dario', 'Carla', 'Bruno', 'Fabio', 'Anna'], $ranking->pluck('first_name')->all());

        $this->assertEquals([
            ['position' => 'above', 'first_name' => 'Elena', 'criterion' => 'race_1_best_lap_time', 'value' => '1:01.000', 'other_value' => '1:00.000'],
            ['position' => 'below', 'first_name' => 'Carla', 'criterion' => 'qualifying_best_lap_time', 'value' => '57.500', 'other_value' => '58.000'],
        ], array_map(fn (array $tieBreak) => Arr::except($tieBreak, ['last_name', 'bib']), $ranking[1]['tie_breaks']));

        $this->assertEquals('race_2_best_lap_time', $ranking[2]['tie_breaks'][1]['criterion']);
        $this->assertEquals('session_bonus', $ranking[5]['tie_breaks'][0]['criterion']);
        $this->assertEquals([2, 5], [$ranking[5]['tie_breaks'][0]['value'], $ranking[5]['tie_breaks'][0]['other_value']]);

        $breakdown = app(CalculateAwardRanking::class)->breakdown($award, $participants['Carla']->racer_hash);

        $this->assertEquals(3, $breakdown['day_ranking']['position']);
        $this->assertEquals('59.900', $breakdown['day_ranking']['race_2_best_lap_time']);
        $this->assertCount(2, $breakdown['day_ranking']['tie_breaks']);
    }

    public function test_day_award_reports_ties_not_resolved_by_any_tie_break(): void
    {
        $championship = Championship::factory()->create();
        $category = Category::factory()->recycle($championship)->create();
        $race = Race::factory()->recycle($championship)->create();
        $first = Participant::factory()->recycle($championship)->recycle($race)->category($category)->create();
        $second = Participant::factory()->recycle($championship)->recycle($race)->category($category)->create();

        $qualifying = RunResult::factory()->published()->recycle($race)->create(['run_type' => RunType::QUALIFY]);
        $this->createResult($qualifying, $first, 10, bestLapTime: '55.000');
        $this->createResult($qualifying, $second, 10, bestLapTime: '55.000');

        $award = ChampionshipAward::factory()->dayAward()->recycle($championship)->create();
        $award->categories()->sync([$category->getKey()]);
        $award->races()->sync([$race->getKey()]);

        $ranking = app(CalculateAwardRanking::class)($award);

        $this->assertNull($ranking[0]['tie_breaks'][0]['criterion']);
        $this->assertNull($ranking[1]['tie_breaks'][0]['criterion']);
    }

    public function test_breakdown_returns_empty_result_for_racer_with_no_points(): void
    {
        $championship = Championship::factory()->create();
        $category = Category::factory()->create(['championship_id' => $championship->getKey()]);

        $award = ChampionshipAward::factory()->create([
            'championship_id' => $championship->getKey(),
            'category_id' => $category->getKey(),
        ]);

        $breakdown = app(CalculateAwardRanking::class)->breakdown($award, 'nonexistent-hash');

        $this->assertEquals(0.0, $breakdown['total_points']);
        $this->assertTrue($breakdown['entries_by_race']->isEmpty());
    }

    private function createResult(RunResult $runResult, Participant $participant, float $points, bool $dns = false, bool $dnf = false, ?string $bestLapTime = null): ParticipantResult
    {
        return ParticipantResult::factory()->forParticipant($participant)->create(array_filter([
            'run_result_id' => $runResult->getKey(),
            'points' => $points,
            'is_dns' => $dns,
            'is_dnf' => $dnf,
            'status' => match (true) {
                $dns => ResultStatus::DID_NOT_START,
                $dnf => ResultStatus::DID_NOT_FINISH,
                default => ResultStatus::FINISHED,
            },
            'best_lap_time' => $bestLapTime,
        ], fn ($value) => $value !== null));
    }
}

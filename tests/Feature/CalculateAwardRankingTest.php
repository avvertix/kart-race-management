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
use App\Models\RunResult;
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
}

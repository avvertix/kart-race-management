<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\AssignPointsToRunResult;
use App\Models\ChampionshipPointScheme;
use App\Models\ParticipantResult;
use App\Models\Race;
use App\Models\RunResult;
use App\Models\RunType;
use App\Models\User;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class PromoteHeatResultTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_a_promoted_heat_is_scored_and_published_as_the_race(): void
    {
        $user = User::factory()->admin()->create();
        $race = Race::factory()->create();
        $heat = $this->makeRun($race, RunType::HEAT, ['title' => 'Heat A']);
        $winner = $this->row($heat, 1, ['position_in_category' => '1']);
        $this->row($heat, 2, ['position_in_category' => '2']);
        $this->row($heat, 3, ['position_in_category' => '3']);

        $this->actingAs($user)
            ->post(route('results.promotion.store', $heat), ['run_type' => RunType::RACE_2->value])
            ->assertRedirectToRoute('races.results.index', $race)
            ->assertSessionHasNoErrors();

        $heat->refresh();

        $this->assertSame(RunType::HEAT, $heat->run_type);
        $this->assertSame(RunType::RACE_2, $heat->promoted_to);

        app(AssignPointsToRunResult::class)($heat, ChampionshipPointScheme::factory()->create());

        $this->assertEquals(25, $winner->fresh()->points);

        $this->actingAs($user)->post(route('races.results.publish-all', $race));

        $this->assertTrue($heat->fresh()->isPublished());
        $this->get(route('public.results.show', $heat))->assertOk();
        $this->get(route('public.races.results.index', $race))
            ->assertSee(RunType::RACE_2->localizedName())
            ->assertSee('Heat A');
    }

    public function test_a_category_split_across_the_promoted_heats_is_counted_as_one_race(): void
    {
        $race = Race::factory()->create();
        $heatOne = $this->makeRun($race, RunType::HEAT, ['promoted_to' => RunType::RACE_1]);
        $winner = $this->row($heatOne, 1, ['position_in_category' => '1']);
        $this->row($heatOne, 3, ['position_in_category' => '2']);
        $heatTwo = $this->makeRun($race, RunType::HEAT, ['promoted_to' => RunType::RACE_1]);
        $this->row($heatTwo, 2, ['position_in_category' => '1']);
        $this->row($heatTwo, 4, ['position_in_category' => '2']);

        app(AssignPointsToRunResult::class)($heatOne, ChampionshipPointScheme::factory()->create());

        // Four drivers in the race, above the small category threshold: no reduction
        $this->assertEquals(25, $winner->fresh()->points);
    }

    public function test_a_heat_cannot_be_promoted_over_a_race_result_of_the_same_category(): void
    {
        $user = User::factory()->admin()->create();
        $race = Race::factory()->create();
        $heat = $this->makeRun($race, RunType::HEAT);
        $this->row($heat, 1);
        $this->row($this->makeRun($race, RunType::RACE_1, ['is_generated' => true]), 1);

        $this->actingAs($user)
            ->post(route('results.promotion.store', $heat), ['run_type' => RunType::RACE_1->value])
            ->assertSessionHasErrors(['promote' => __('Already in a :run result: :categories.', ['run' => RunType::RACE_1->localizedName(), 'categories' => 'KZ'])]);

        $this->assertNull($heat->fresh()->promoted_to);
    }

    public function test_a_heat_can_be_promoted_when_other_categories_have_their_own_race(): void
    {
        $user = User::factory()->admin()->create();
        $race = Race::factory()->create();
        $heat = $this->makeRun($race, RunType::HEAT);
        $this->row($heat, 1);
        $this->row($this->makeRun($race, RunType::RACE_1), 50, ['category' => 'MINI']);

        $this->actingAs($user)
            ->post(route('results.promotion.store', $heat), ['run_type' => RunType::RACE_1->value])
            ->assertSessionHasNoErrors();

        $this->assertSame(RunType::RACE_1, $heat->fresh()->promoted_to);
    }

    public function test_only_heats_can_be_promoted(): void
    {
        $user = User::factory()->admin()->create();
        $series = $this->makeRun(Race::factory()->create(), RunType::QUALIFY_SERIES);

        $this->actingAs($user)
            ->post(route('results.promotion.store', $series), ['run_type' => RunType::RACE_1->value])
            ->assertSessionHasErrors('promote');

        $this->assertNull($series->fresh()->promoted_to);
    }

    public function test_removing_the_promotion_clears_points_and_unpublishes_the_heat(): void
    {
        $user = User::factory()->admin()->create();
        $heat = $this->makeRun(Race::factory()->create(), RunType::HEAT, ['promoted_to' => RunType::RACE_1, 'published_at' => now()]);
        $row = $this->row($heat, 1, ['position_in_category' => '1', 'points' => 25]);

        $this->actingAs($user)
            ->delete(route('results.promotion.destroy', $heat))
            ->assertSessionHasNoErrors();

        $heat->refresh();

        $this->assertNull($heat->promoted_to);
        $this->assertNull($heat->published_at);
        $this->assertNull($row->fresh()->points);
    }

    public function test_heats_promoted_as_a_race_cannot_also_be_classified_as_that_race(): void
    {
        $user = User::factory()->admin()->create();
        $race = Race::factory()->create();
        $this->makeRun($race, RunType::HEAT, ['promoted_to' => RunType::RACE_1]);

        $this->actingAs($user)
            ->post(route('races.results.combine-heats', $race), ['run_type' => RunType::RACE_1->value])
            ->assertSessionHasErrors('combine');

        $this->assertSame(0, $race->results()->where('is_generated', true)->count());
    }

    private function makeRun(Race $race, RunType $type, array $attributes = []): RunResult
    {
        return RunResult::factory()->create([
            'race_id' => $race->getKey(),
            'run_type' => $type,
            ...$attributes,
        ]);
    }

    private function row(RunResult $run, int $bib, array $attributes = []): ParticipantResult
    {
        return ParticipantResult::factory()->create([
            'run_result_id' => $run->getKey(),
            'participant_id' => null,
            'bib' => $bib,
            'name' => "Driver {$bib}",
            'category' => 'KZ',
            ...$attributes,
        ]);
    }
}

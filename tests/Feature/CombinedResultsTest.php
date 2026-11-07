<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\AssignPointsToRunResult;
use App\Actions\BuildRaceHeatGrids;
use App\Models\ChampionshipPointScheme;
use App\Models\ParticipantResult;
use App\Models\Race;
use App\Models\RunResult;
use App\Models\RunType;
use App\Models\User;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class CombinedResultsTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_qualifying_series_are_combined_in_a_single_ranked_qualifying(): void
    {
        $user = User::factory()->admin()->create();
        $race = Race::factory()->create();

        $first = $this->makeRun($race, RunType::QUALIFY_SERIES);
        $second = $this->makeRun($race, RunType::QUALIFY_SERIES);

        $this->row($first, 1, ['best_lap_time' => '50.000']);
        $this->row($first, 2, ['best_lap_time' => '50.500']);
        $this->row($second, 3, ['best_lap_time' => '50.200']);
        $this->row($second, 4, ['best_lap_time' => '50.800']);

        $this->actingAs($user)
            ->post(route('races.results.combine-qualifying', $race))
            ->assertRedirectToRoute('races.results.index', $race);

        $combined = $race->results()->where('is_generated', true)->sole();

        $this->assertSame(RunType::QUALIFY, $combined->run_type);

        $rows = $combined->participantResults()->orderBy('id')->get();

        $this->assertSame([1, 3, 2, 4], $rows->pluck('bib')->all());
        $this->assertSame(['1', '2', '3', '4'], $rows->pluck('position_in_category')->all());
        $this->assertSame(['', '0.200', '0.500', '0.800'], $rows->pluck('gap_from_leader')->all());
        $this->assertSame(['', '0.200', '0.300', '0.300'], $rows->pluck('gap_from_previous')->all());

        $this->assertSame(2, $race->results()->where('run_type', RunType::QUALIFY_SERIES->value)->count());
    }

    public function test_combining_again_replaces_the_previous_one_and_keeps_it_published(): void
    {
        $user = User::factory()->admin()->create();
        $race = Race::factory()->create();

        $series = $this->makeRun($race, RunType::QUALIFY_SERIES);
        $this->row($series, 1, ['best_lap_time' => '50.000']);

        $this->actingAs($user)->post(route('races.results.combine-qualifying', $race));

        $race->results()->where('is_generated', true)->update(['published_at' => now()]);

        $this->row($series, 2, ['best_lap_time' => '49.000']);

        $this->actingAs($user)->post(route('races.results.combine-qualifying', $race));

        $combined = $race->results()->where('is_generated', true)->sole();

        $this->assertTrue($combined->isPublished());
        $this->assertSame([2, 1], $combined->participantResults()->orderBy('id')->pluck('bib')->all());
    }

    public function test_combining_qualifying_without_series_is_refused(): void
    {
        $user = User::factory()->admin()->create();
        $race = Race::factory()->create();

        $this->actingAs($user)
            ->from(route('races.heat-grids.index', $race))
            ->post(route('races.results.combine-qualifying', $race))
            ->assertRedirectToRoute('races.heat-grids.index', $race)
            ->assertSessionHasErrors('combine');

        $this->assertSame(0, $race->results()->count());
    }

    public function test_combining_qualifying_is_refused_when_the_category_is_already_in_an_uploaded_qualifying(): void
    {
        $user = User::factory()->admin()->create();
        $race = Race::factory()->create();

        $this->row($this->makeRun($race, RunType::QUALIFY_SERIES), 1, ['best_lap_time' => '50.000']);
        $this->row($this->makeRun($race, RunType::QUALIFY), 2, ['best_lap_time' => '50.100']);

        $this->actingAs($user)
            ->post(route('races.results.combine-qualifying', $race))
            ->assertSessionHasErrors('combine');

        $this->assertSame(0, $race->results()->where('is_generated', true)->count());
    }

    public function test_heats_are_classified_following_the_prefinale_grid_keeping_their_own_times(): void
    {
        $user = User::factory()->admin()->create();
        $race = Race::factory()->create();

        $qualifying = $this->makeRun($race, RunType::QUALIFY_SERIES);
        foreach ([1, 2, 3, 4, 5] as $bib) {
            $this->row($qualifying, $bib, ['best_lap_time' => '50.'.mb_str_pad((string) $bib, 3, '0', STR_PAD_LEFT)]);
        }

        $heatOne = $this->makeRun($race, RunType::HEAT);
        $this->row($heatOne, 1, ['position' => '1', 'laps' => 9, 'total_race_time' => '8:30.000', 'best_lap_time' => '54.000']);
        $this->row($heatOne, 3, ['position' => '2', 'laps' => 9, 'total_race_time' => '8:31.000', 'best_lap_time' => '54.100']);
        $this->row($heatOne, 5, ['position' => '3', 'laps' => 8, 'total_race_time' => '8:40.000', 'best_lap_time' => '54.300']);

        $heatTwo = $this->makeRun($race, RunType::HEAT);
        $this->row($heatTwo, 2, ['position' => '1', 'laps' => 9, 'total_race_time' => '8:20.000', 'best_lap_time' => '53.900']);
        $this->row($heatTwo, 4, ['position' => '2', 'laps' => 9, 'total_race_time' => '8:25.000', 'best_lap_time' => '54.200']);

        $this->actingAs($user)
            ->post(route('races.results.combine-heats', $race), ['run_type' => RunType::RACE_1->value])
            ->assertRedirectToRoute('races.results.index', $race);

        $combined = $race->results()->where('is_generated', true)->sole();

        $this->assertSame(RunType::RACE_1, $combined->run_type);

        $rows = $combined->participantResults()->orderBy('id')->get();

        $this->assertSame([2, 1, 4, 3, 5], $rows->pluck('bib')->all());
        $this->assertSame(['1', '2', '3', '4', '5'], $rows->pluck('position_in_category')->all());
        $this->assertSame([9, 9, 9, 9, 8], $rows->pluck('laps')->all());
        $this->assertSame('8:20.000', $rows->first()->total_race_time);
    }

    public function test_heats_classification_keeps_drivers_beyond_the_track_capacity(): void
    {
        $user = User::factory()->admin()->create();
        $race = Race::factory()->create();

        $qualifying = $this->makeRun($race, RunType::QUALIFY_SERIES);
        $heatOne = $this->makeRun($race, RunType::HEAT);
        $heatTwo = $this->makeRun($race, RunType::HEAT);

        foreach (range(1, 20) as $position) {
            $this->row($qualifying, $position, ['best_lap_time' => '50.000']);
            $this->row($qualifying, 100 + $position, ['best_lap_time' => '50.000']);
            $this->row($heatOne, $position, ['position' => (string) $position, 'total_race_time' => '8:30.000']);
            $this->row($heatTwo, 100 + $position, ['position' => (string) $position, 'total_race_time' => '8:40.000']);
        }

        $this->actingAs($user)->post(route('races.results.combine-heats', $race), ['run_type' => RunType::RACE_1->value]);

        $combined = $race->results()->where('is_generated', true)->sole();

        $this->assertSame(40, $combined->participantResults()->count());
        $this->assertSame('40', $combined->participantResults()->orderByDesc('id')->first()->position_in_category);
    }

    public function test_the_track_capacity_applies_to_all_the_categories_together(): void
    {
        $user = User::factory()->admin()->create();
        $race = Race::factory()->create();

        $heatOne = $this->makeRun($race, RunType::HEAT);
        $heatTwo = $this->makeRun($race, RunType::HEAT);

        // Four categories of ten drivers racing together in two heats of twenty
        foreach (['A', 'B', 'C', 'D'] as $categoryIndex => $category) {
            $qualifying = $this->makeRun($race, RunType::QUALIFY_SERIES);

            foreach (range(1, 10) as $number) {
                $bib = ($categoryIndex * 10) + $number;
                $heat = $bib % 2 === 1 ? $heatOne : $heatTwo;

                $this->row($qualifying, $bib, ['category' => $category, 'best_lap_time' => '50.000']);
                $this->row($heat, $bib, [
                    'category' => $category,
                    'position' => (string) (intdiv($bib - 1, 2) + 1),
                    'total_race_time' => $heat->is($heatOne) ? '8:30.000' : '8:40.000',
                ]);
            }
        }

        $prefinale = app(BuildRaceHeatGrids::class)->prefinale($race);

        $this->assertCount(36, $prefinale['grid']);
        $this->assertCount(4, $prefinale['not_admitted']);
        $this->assertGreaterThan(1, $prefinale['grid']->map(fn (array $row) => $row['result']->category)->unique()->count());

        $this->actingAs($user)->post(route('races.results.combine-heats', $race), ['run_type' => RunType::RACE_1->value]);

        $rows = $race->results()->where('is_generated', true)->sole()->participantResults()->orderBy('id')->get();

        $this->assertCount(40, $rows);
        $this->assertSame(range(1, 40), $rows->pluck('position')->map(fn ($position) => (int) $position)->all());

        foreach (['A', 'B', 'C', 'D'] as $category) {
            $this->assertSame(range(1, 10), $rows->where('category', $category)->pluck('position_in_category')->map(fn ($position) => (int) $position)->values()->all());
        }
    }

    public function test_heats_cannot_be_classified_over_an_uploaded_race_result(): void
    {
        $user = User::factory()->admin()->create();
        $race = Race::factory()->create();

        $this->row($this->makeRun($race, RunType::QUALIFY_SERIES), 1, ['best_lap_time' => '50.000']);
        $this->row($this->makeRun($race, RunType::HEAT), 1, ['position' => '1', 'total_race_time' => '8:30.000']);
        $this->makeRun($race, RunType::RACE_1);

        $this->actingAs($user)
            ->post(route('races.results.combine-heats', $race), ['run_type' => RunType::RACE_1->value])
            ->assertSessionHasErrors('combine');
    }

    public function test_heats_can_only_be_classified_as_a_race(): void
    {
        $user = User::factory()->admin()->create();
        $race = Race::factory()->create();

        $this->actingAs($user)
            ->post(route('races.results.combine-heats', $race), ['run_type' => RunType::QUALIFY->value])
            ->assertSessionHasErrors('run_type');
    }

    public function test_series_and_heats_are_never_published(): void
    {
        $user = User::factory()->admin()->create();
        $race = Race::factory()->create();

        $series = $this->makeRun($race, RunType::QUALIFY_SERIES);
        $heat = $this->makeRun($race, RunType::HEAT);
        $qualifying = $this->makeRun($race, RunType::QUALIFY);

        $this->actingAs($user)->post(route('races.results.publish-all', $race));

        $this->assertNull($series->fresh()->published_at);
        $this->assertNull($heat->fresh()->published_at);
        $this->assertNotNull($qualifying->fresh()->published_at);

        $this->actingAs($user)->post(route('results.toggle-publish', $heat))->assertStatus(422);

        $heat->forceFill(['published_at' => now()])->save();

        $this->get(route('public.results.show', $heat))->assertNotFound();
        $this->get(route('public.races.results.index', $race))
            ->assertSee($qualifying->title)
            ->assertDontSee($heat->title);
    }

    public function test_points_are_not_assigned_to_series_and_heats(): void
    {
        $race = Race::factory()->create();
        $series = $this->makeRun($race, RunType::QUALIFY_SERIES);
        $row = $this->row($series, 1, ['position_in_category' => '1']);

        app(AssignPointsToRunResult::class)($series, ChampionshipPointScheme::factory()->create());

        $this->assertNull($row->fresh()->points);
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
            'category_id' => null,
            'bib' => $bib,
            'name' => "Driver {$bib}",
            'category' => 'KZ',
            ...$attributes,
        ]);
    }
}

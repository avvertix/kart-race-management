<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\CalculateHeatAssignment;
use App\Actions\CalculatePrefinaleGrid;
use App\Actions\CalculateQualifyingRanking;
use App\Models\ParticipantResult;
use App\Models\Race;
use App\Models\ResultStatus;
use App\Models\RunResult;
use App\Models\RunType;
use App\Models\User;
use App\Support\LapTime;
use Illuminate\Support\Collection;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class HeatGridTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_heat_session_is_recognized_from_file_name(): void
    {
        $this->assertSame(RunType::HEAT, RunType::fromString('3 - BATTERIA 1 - 125 KZ N OVER - Results.xml'));
        $this->assertSame(RunType::HEAT, RunType::fromString('Batterie - 125 KZ N OVER - Results.xml'));
        $this->assertSame(RunType::HEAT, RunType::fromString('Heat 2 - 125 KZ N OVER - Results.xml'));
        $this->assertTrue(RunType::HEAT->isRace());
        $this->assertTrue(RunType::HEAT->isHeat());
        $this->assertFalse(RunType::RACE_1->isHeat());
    }

    public function test_lap_time_is_converted_to_milliseconds(): void
    {
        $this->assertSame(54201, LapTime::toMilliseconds('54.201'));
        $this->assertSame(62345, LapTime::toMilliseconds('1:02.345'));
        $this->assertSame(3723456, LapTime::toMilliseconds('1:02:03.456'));
        $this->assertNull(LapTime::toMilliseconds(''));
        $this->assertNull(LapTime::toMilliseconds(null));
        $this->assertNull(LapTime::toMilliseconds('DNS'));
    }

    public function test_single_series_is_ranked_by_best_lap_with_second_best_as_tie_break(): void
    {
        $ranking = (new CalculateQualifyingRanking)(collect([
            $this->qualifying(1, 10, '50.300'),
            $this->qualifying(1, 11, '50.100', '50.900'),
            $this->qualifying(1, 12, '50.100', '50.500'),
        ]));

        $this->assertSame([12, 11, 10], $this->bibs($ranking['ranked']));
        $this->assertSame('single', $ranking['method']);
    }

    public function test_drivers_without_time_are_last_and_disqualified_are_left_out(): void
    {
        $ranking = (new CalculateQualifyingRanking)(collect([
            $this->qualifying(1, 10, null),
            $this->qualifying(1, 11, '50.100'),
            $this->qualifying(1, 12, '49.000', dq: true),
            $this->qualifying(1, 13, '51.000', dns: true),
        ]));

        $this->assertSame([11], $this->bibs($ranking['ranked']));
        $this->assertSame([10, 13], $this->bibs($ranking['untimed']));
        $this->assertSame([12], $this->bibs($ranking['excluded']));
    }

    public function test_series_within_one_percent_are_ranked_by_time(): void
    {
        $ranking = (new CalculateQualifyingRanking)(collect([
            $this->qualifying(1, 10, '50.000'),
            $this->qualifying(1, 11, '51.500'),
            $this->qualifying(2, 20, '50.400'),
            $this->qualifying(2, 21, '50.600'),
        ]));

        $this->assertSame('time', $ranking['method']);
        $this->assertSame([10, 20, 21, 11], $this->bibs($ranking['ranked']));
    }

    public function test_series_over_one_percent_are_ranked_by_series_position_then_time(): void
    {
        $ranking = (new CalculateQualifyingRanking)(collect([
            $this->qualifying(1, 10, '50.000'),
            $this->qualifying(1, 11, '50.200'),
            $this->qualifying(2, 20, '51.000'),
            $this->qualifying(2, 21, '51.100'),
        ]));

        $this->assertSame('position', $ranking['method']);
        $this->assertSame([10, 20, 11, 21], $this->bibs($ranking['ranked']));
    }

    public function test_heats_are_assigned_in_turn_following_the_qualifying_ranking(): void
    {
        $ranking = (new CalculateQualifyingRanking)(collect([
            $this->qualifying(1, 1, '50.000'),
            $this->qualifying(1, 2, '50.100'),
            $this->qualifying(1, 3, '50.200'),
            $this->qualifying(1, 4, '50.300'),
            $this->qualifying(1, 5, '50.400'),
            $this->qualifying(1, 6, '50.500'),
            $this->qualifying(1, 7, '50.600'),
            $this->qualifying(1, 8, null),
        ]));

        $assignment = (new CalculateHeatAssignment)($ranking, 3);

        $this->assertSame([1, 4, 7], $this->bibs($assignment[1]));
        $this->assertSame([2, 5, 8], $this->bibs($assignment[2]));
        $this->assertSame([3, 6], $this->bibs($assignment[3]));
        $this->assertTrue($assignment[2]->last()['drawn']);
        $this->assertSame(3, $assignment[1]->last()['grid']);
    }

    public function test_prefinale_grid_with_two_heats_alternates_starting_from_the_fastest_winner(): void
    {
        $grid = (new CalculatePrefinaleGrid)(collect([
            collect([
                $this->heatRow(1, 1, 1, '8:30.000'),
                $this->heatRow(1, 3, 2, '8:31.000'),
                $this->heatRow(1, 5, 3, '8:32.000'),
            ]),
            collect([
                $this->heatRow(2, 2, 1, '8:20.000'),
                $this->heatRow(2, 4, 2, '8:21.000'),
                $this->heatRow(2, 6, 3, '8:22.000'),
            ]),
        ]), collect());

        $this->assertSame([2, 1, 4, 3, 6, 5], $this->bibs($grid['grid']));
        $this->assertSame([2, 1], $grid['heats']->map(fn ($heat) => $heat['winner']->bib)->all());
    }

    public function test_prefinale_grid_with_three_heats_keeps_each_winner_followed_by_the_others_of_the_same_round(): void
    {
        $grid = (new CalculatePrefinaleGrid)(collect([
            collect([$this->heatRow(1, 1, 1, '8:30.000'), $this->heatRow(1, 4, 2, '8:40.000')]),
            collect([$this->heatRow(2, 2, 1, '8:10.000'), $this->heatRow(2, 5, 2, '8:50.000')]),
            collect([$this->heatRow(3, 3, 1, '8:20.000'), $this->heatRow(3, 6, 2, '8:45.000'), $this->heatRow(3, 9, 3, '9:00.000')]),
        ]), collect());

        $this->assertSame([2, 3, 1, 5, 6, 4, 9], $this->bibs($grid['grid']));
    }

    public function test_prefinale_tie_on_winner_total_time_is_broken_by_best_lap_then_qualifying(): void
    {
        $byBestLap = (new CalculatePrefinaleGrid)(collect([
            collect([$this->heatRow(1, 1, 1, '8:30.000', '50.200')]),
            collect([$this->heatRow(2, 2, 1, '8:30.000', '50.100')]),
        ]), collect());

        $this->assertSame([2, 1], $this->bibs($byBestLap['grid']));

        $byQualifying = (new CalculatePrefinaleGrid)(collect([
            collect([$this->heatRow(1, 1, 1, '8:30.000', '50.100')]),
            collect([$this->heatRow(2, 2, 1, '8:30.000', '50.100')]),
        ]), collect([1 => 5, 2 => 3]));

        $this->assertSame([2, 1], $this->bibs($byQualifying['grid']));
    }

    public function test_prefinale_grid_holds_at_most_the_track_capacity(): void
    {
        $heatOne = collect(range(1, 20))->map(fn (int $position) => $this->heatRow(1, $position, $position, '8:30.000'));
        $heatTwo = collect(range(1, 20))->map(fn (int $position) => $this->heatRow(2, 100 + $position, $position, '8:40.000'));

        $grid = (new CalculatePrefinaleGrid)(collect([$heatOne, $heatTwo]), collect(), 36);

        $this->assertCount(36, $grid['grid']);
        $this->assertSame(36, $grid['grid']->last()['position']);
        $this->assertSame(18, (int) ceil($grid['grid']->last()['position'] / 2));
        $this->assertSame([19, 119, 20, 120], $this->bibs($grid['not_admitted']));
    }

    public function test_prefinale_grid_leaves_out_drivers_who_did_not_start(): void
    {
        $grid = (new CalculatePrefinaleGrid)(collect([
            collect([
                $this->heatRow(1, 1, 1, '8:30.000'),
                $this->heatRow(1, 3, 2, '', dns: true),
            ]),
        ]), collect());

        $this->assertSame([1], $this->bibs($grid['grid']));
        $this->assertSame([3], $this->bibs($grid['excluded']));
    }

    public function test_heat_grids_page_shows_assignment_and_prefinale_grid(): void
    {
        $user = User::factory()->admin()->create();
        $race = Race::factory()->create();

        $qualifying = RunResult::factory()->create(['race_id' => $race->getKey(), 'run_type' => RunType::QUALIFY]);

        foreach ([1 => '50.000', 2 => '50.100', 3 => '50.200', 4 => '50.300'] as $bib => $time) {
            ParticipantResult::factory()->create([
                'run_result_id' => $qualifying->getKey(),
                'category_id' => null,
                'bib' => $bib,
                'name' => "Driver {$bib}",
                'category' => 'KZ',
                'best_lap_time' => $time,
            ]);
        }

        foreach ([1 => [1, 3], 2 => [2, 4]] as $heat => $bibs) {
            $run = RunResult::factory()->create(['race_id' => $race->getKey(), 'run_type' => RunType::HEAT]);

            foreach ($bibs as $index => $bib) {
                ParticipantResult::factory()->create([
                    'run_result_id' => $run->getKey(),
                    'category_id' => null,
                    'bib' => $bib,
                    'name' => "Driver {$bib}",
                    'category' => 'KZ',
                    'position' => (string) ($index + 1),
                    'total_race_time' => $heat === 1 ? '8:30.000' : '8:20.000',
                ]);
            }
        }

        $response = $this->actingAs($user)->get(route('races.heat-grids.index', $race));

        $response->assertSuccessful();

        $grid = $response->viewData('grid');

        $this->assertSame(['KZ'], $grid['categories']->all());
        $this->assertSame([1, 3], $this->bibs($grid['assignment'][1]));
        $this->assertSame([2, 4], $this->bibs($grid['assignment'][2]));
        $this->assertSame([2, 1, 4, 3], $this->bibs($response->viewData('prefinale')['grid']));
    }

    public function test_every_heat_contains_all_the_classes_of_the_qualifying(): void
    {
        $user = User::factory()->admin()->create();
        $race = Race::factory()->create();

        // One qualifying series with two classes: the heats are made on the whole field
        $qualifying = RunResult::factory()->create(['race_id' => $race->getKey(), 'run_type' => RunType::QUALIFY_SERIES]);

        foreach ([1 => ['A', '50.000'], 2 => ['A', '50.100'], 3 => ['B', '50.200'], 4 => ['B', '50.300'], 5 => ['A', '50.400'], 6 => ['B', '50.500']] as $bib => [$category, $time]) {
            ParticipantResult::factory()->create([
                'run_result_id' => $qualifying->getKey(),
                'category_id' => null,
                'bib' => $bib,
                'category' => $category,
                'best_lap_time' => $time,
            ]);
        }

        $response = $this->actingAs($user)->get(route('races.heat-grids.index', $race));

        $response->assertSuccessful();

        $grid = $response->viewData('grid');

        $this->assertSame(['A', 'B'], $grid['categories']->all());
        $this->assertSame(2, $grid['heats']);
        $this->assertSame([1, 3, 5], $this->bibs($grid['assignment'][1]));
        $this->assertSame([2, 4, 6], $this->bibs($grid['assignment'][2]));
        $this->assertSame(['A', 'B', 'A'], $grid['assignment'][1]->map(fn ($entry) => $entry['result']->category)->all());
        $this->assertSame(['A', 'B', 'B'], $grid['assignment'][2]->map(fn ($entry) => $entry['result']->category)->all());
    }

    public function test_heat_grids_page_requires_authentication(): void
    {
        $race = Race::factory()->create();

        $this->get(route('races.heat-grids.index', $race))->assertRedirectToRoute('login');
    }

    private function qualifying(int $series, int $bib, ?string $best, ?string $second = null, bool $dns = false, bool $dq = false): ParticipantResult
    {
        return ParticipantResult::factory()->make([
            'run_result_id' => $series,
            'category_id' => null,
            'bib' => $bib,
            'name' => "Driver {$bib}",
            'category' => 'KZ',
            'best_lap_time' => $best ?? '',
            'second_best_time' => $second,
            'is_dns' => $dns,
            'is_dq' => $dq,
            'status' => $dq ? ResultStatus::DISQUALIFIED : ($dns ? ResultStatus::DID_NOT_START : ResultStatus::FINISHED),
        ]);
    }

    private function heatRow(int $heat, int $bib, int $position, string $total, string $best = '50.000', bool $dns = false): ParticipantResult
    {
        return ParticipantResult::factory()->make([
            'run_result_id' => $heat,
            'category_id' => null,
            'bib' => $bib,
            'name' => "Driver {$bib}",
            'category' => 'KZ',
            'position' => (string) $position,
            'total_race_time' => $total,
            'best_lap_time' => $best,
            'is_dns' => $dns,
        ]);
    }

    /**
     * @param  Collection<int, mixed>  $rows
     * @return array<int, int>
     */
    private function bibs(Collection $rows): array
    {
        return $rows->map(fn ($row) => ($row instanceof ParticipantResult ? $row : $row['result'])->bib)->all();
    }
}

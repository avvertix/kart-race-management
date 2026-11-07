<?php

declare(strict_types=1);

namespace Tests\Feature\Exports;

use App\Models\Participant;
use App\Models\ParticipantResult;
use App\Models\Race;
use App\Models\RunResult;
use App\Models\RunType;
use App\Models\Transponder;
use App\Models\User;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class RaceGridForTimingExportTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_export_requires_authentication(): void
    {
        $race = Race::factory()->create();

        $this->get(route('races.export.prefinale-grid', $race))->assertRedirect(route('login'));
    }

    public function test_export_forbidden_for_tireagent(): void
    {
        $user = User::factory()->tireagent()->create();
        $race = Race::factory()->create();

        $this->actingAs($user)->get(route('races.export.prefinale-grid', $race))->assertForbidden();
    }

    public function test_export_lists_the_prefinale_grid_in_starting_order(): void
    {
        $user = User::factory()->timekeeper()->create();
        $race = Race::factory()->create();

        $qualifying = RunResult::factory()->create(['race_id' => $race->getKey(), 'run_type' => RunType::QUALIFY]);

        foreach ([1 => '50.000', 2 => '50.100', 3 => '50.200', 4 => '50.300'] as $bib => $time) {
            ParticipantResult::factory()->create([
                'run_result_id' => $qualifying->getKey(),
                'category_id' => null,
                'bib' => $bib,
                'category' => 'KZ',
                'best_lap_time' => $time,
            ]);

            Participant::factory()
                ->recycle($race->championship)
                ->category()
                ->has(Transponder::factory()->state(['code' => 100 + $bib, 'race_id' => $race->getKey()]), 'transponders')
                ->create(['bib' => $bib, 'race_id' => $race->getKey()]);
        }

        foreach ([1 => [1, 3], 2 => [2, 4]] as $heat => $bibs) {
            $run = RunResult::factory()->create(['race_id' => $race->getKey(), 'run_type' => RunType::HEAT]);

            foreach ($bibs as $index => $bib) {
                ParticipantResult::factory()->create([
                    'run_result_id' => $run->getKey(),
                    'category_id' => null,
                    'bib' => $bib,
                    'category' => 'KZ',
                    'position' => (string) ($index + 1),
                    'total_race_time' => $heat === 1 ? '8:30.000' : '8:20.000',
                ]);
            }
        }

        $response = $this->actingAs($user)->get(route('races.export.prefinale-grid', $race));

        $response->assertSuccessful();

        $lines = array_values(array_filter(preg_split('/\r?\n/', $response->streamedContent())));

        $this->assertStringStartsWith('No;Class;LastName;FirstName', $lines[0]);
        $this->assertSame(
            ['2', '1', '4', '3'],
            array_map(fn (string $line) => explode(';', $line)[0], array_slice($lines, 1)),
        );
        $this->assertStringContainsString('102', $lines[1]);
    }

    public function test_heat_export_lists_the_drivers_of_the_heat_from_qualifying_only(): void
    {
        $user = User::factory()->timekeeper()->create();
        $race = Race::factory()->create();

        $qualifying = RunResult::factory()->create(['race_id' => $race->getKey(), 'run_type' => RunType::QUALIFY]);

        foreach ([1 => '50.000', 2 => '50.100', 3 => '50.200', 4 => '50.300', 5 => '50.400'] as $bib => $time) {
            ParticipantResult::factory()->create([
                'run_result_id' => $qualifying->getKey(),
                'category_id' => null,
                'bib' => $bib,
                'category' => 'KZ',
                'best_lap_time' => $time,
            ]);

            Participant::factory()
                ->recycle($race->championship)
                ->category()
                ->has(Transponder::factory()->state(['code' => 100 + $bib, 'race_id' => $race->getKey()]), 'transponders')
                ->create(['bib' => $bib, 'race_id' => $race->getKey()]);
        }

        $response = $this->actingAs($user)->get(route('races.export.heat-grid', ['race' => $race, 'heat' => 2]));

        $response->assertSuccessful();

        $lines = array_values(array_filter(preg_split('/\r?\n/', $response->streamedContent())));

        $this->assertSame(
            ['2', '4'],
            array_map(fn (string $line) => explode(';', $line)[0], array_slice($lines, 1)),
        );

        $this->actingAs($user)->get(route('races.export.heat-grid', ['race' => $race, 'heat' => 3]))->assertNotFound();
    }

    public function test_heat_export_forbidden_for_tireagent(): void
    {
        $user = User::factory()->tireagent()->create();
        $race = Race::factory()->create();

        $this->actingAs($user)->get(route('races.export.heat-grid', ['race' => $race, 'heat' => 1]))->assertForbidden();
    }
}

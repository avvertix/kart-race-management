<?php

declare(strict_types=1);

namespace Tests\Feature\Operations;

use App\Models\Championship;
use App\Models\ChampionshipTire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackfillChampionshipTiresTest extends TestCase
{
    use RefreshDatabase;

    public function test_championship_tires_backfilled_from_races_configuration()
    {
        config([
            'races.tires' => [
                'VEGA_SL4' => [
                    'name' => 'VEGA SL4',
                    'price' => '16500',
                ],
            ],
        ]);

        $championship = Championship::factory()
            ->create();

        $this->artisan('operations:process', [
            '--test' => true,
            'name' => '2023_12_30_120021_backfill_championship_tires',
        ])
            ->assertSuccessful();

        $tires = $championship->fresh()->tires;

        $this->assertCount(1, $tires);

        $tire = $tires->first();

        $this->assertEquals('VEGA_SL4', $tire->code);
        $this->assertEquals('VEGA SL4', $tire->name);
        $this->assertEquals(16500, $tire->price);
    }

    public function test_backfill_only_done_when_no_tires_configured()
    {

        config([
            'races.tires' => [
                'VEGA_SL4' => [
                    'name' => 'VEGA SL4',
                    'price' => '16500',
                ],
            ],
        ]);

        $championship = Championship::factory()
            ->has(ChampionshipTire::factory()->count(1), 'tires')
            ->create();

        $configuredTire = $championship->tires()->first();

        $this->artisan('operations:process', [
            '--test' => true,
            'name' => '2023_12_30_120021_backfill_championship_tires',
        ])
            ->assertSuccessful();

        $tires = $championship->fresh()->tires;

        $this->assertCount(1, $tires);

        $this->assertTrue($configuredTire->is($tires->first()));
    }
}

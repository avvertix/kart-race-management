<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\CopyChampionshipTires;
use App\Models\Championship;
use App\Models\ChampionshipTire;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class CopyChampionshipTiresTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_tires_are_copied_from_source_to_target_championship(): void
    {
        $sourceChampionship = Championship::factory()
            ->has(ChampionshipTire::factory()->count(3), 'tires')
            ->create();

        $targetChampionship = Championship::factory()->create();

        $copiedTires = (new CopyChampionshipTires)($sourceChampionship, $targetChampionship);

        $this->assertCount(3, $copiedTires);
        $this->assertCount(3, $targetChampionship->fresh()->tires);
    }

    public function test_copied_tires_have_new_ulids(): void
    {
        $sourceChampionship = Championship::factory()
            ->has(ChampionshipTire::factory()->count(2), 'tires')
            ->create();

        $targetChampionship = Championship::factory()->create();

        $copiedTires = (new CopyChampionshipTires)($sourceChampionship, $targetChampionship);

        $sourceUlids = $sourceChampionship->tires->pluck('ulid')->toArray();
        $copiedUlids = $copiedTires->pluck('ulid')->toArray();

        $this->assertNotEquals($sourceUlids, $copiedUlids);
        $this->assertCount(2, array_unique($copiedUlids));
    }

    public function test_copied_tires_belong_to_target_championship(): void
    {
        $sourceChampionship = Championship::factory()
            ->has(ChampionshipTire::factory()->count(2), 'tires')
            ->create();

        $targetChampionship = Championship::factory()->create();

        $copiedTires = (new CopyChampionshipTires)($sourceChampionship, $targetChampionship);

        $copiedTires->each(function (ChampionshipTire $tire) use ($targetChampionship) {
            $this->assertEquals($targetChampionship->id, $tire->championship_id);
            $this->assertTrue($tire->championship->is($targetChampionship));
        });
    }

    public function test_tire_properties_are_copied_correctly(): void
    {
        $sourceChampionship = Championship::factory()
            ->has(
                ChampionshipTire::factory()->state([
                    'name' => 'Bridgestone YDS',
                    'code' => 'BG-YDS',
                    'price' => 15000,
                ]),
                'tires'
            )
            ->create();

        $targetChampionship = Championship::factory()->create();

        $copiedTires = (new CopyChampionshipTires)($sourceChampionship, $targetChampionship);

        $this->assertCount(1, $copiedTires);

        $copiedTire = $copiedTires->first();
        $sourceTire = $sourceChampionship->tires->first();

        $this->assertEquals($sourceTire->name, $copiedTire->name);
        $this->assertEquals($sourceTire->code, $copiedTire->code);
        $this->assertEquals($sourceTire->price, $copiedTire->price);
    }

    public function test_source_tires_remain_unchanged(): void
    {
        $sourceChampionship = Championship::factory()
            ->has(ChampionshipTire::factory()->count(2), 'tires')
            ->create();

        $originalSourceTiresCount = $sourceChampionship->tires->count();
        $originalSourceTireIds = $sourceChampionship->tires->pluck('id')->toArray();

        $targetChampionship = Championship::factory()->create();

        (new CopyChampionshipTires)($sourceChampionship, $targetChampionship);

        $sourceChampionship->refresh();

        $this->assertCount($originalSourceTiresCount, $sourceChampionship->tires);
        $this->assertEquals($originalSourceTireIds, $sourceChampionship->tires->pluck('id')->toArray());
    }

    public function test_returns_empty_collection_when_source_has_no_tires(): void
    {
        $sourceChampionship = Championship::factory()->create();
        $targetChampionship = Championship::factory()->create();

        $copiedTires = (new CopyChampionshipTires)($sourceChampionship, $targetChampionship);

        $this->assertCount(0, $copiedTires);
        $this->assertCount(0, $targetChampionship->fresh()->tires);
    }

    public function test_multiple_tires_with_different_properties_are_copied(): void
    {
        $sourceChampionship = Championship::factory()
            ->has(
                ChampionshipTire::factory()->state([
                    'name' => 'Bridgestone YDS',
                    'code' => 'BG-YDS',
                    'price' => 15000,
                ]),
                'tires'
            )
            ->has(
                ChampionshipTire::factory()->state([
                    'name' => 'Vega XH3',
                    'code' => 'VG-XH3',
                    'price' => 12000,
                ]),
                'tires'
            )
            ->has(
                ChampionshipTire::factory()->state([
                    'name' => 'MG SM',
                    'code' => 'MG-SM',
                    'price' => 18000,
                ]),
                'tires'
            )
            ->create();

        $targetChampionship = Championship::factory()->create();

        $copiedTires = (new CopyChampionshipTires)($sourceChampionship, $targetChampionship);

        $this->assertCount(3, $copiedTires);

        $copiedTiresByCode = $copiedTires->keyBy('code');

        $this->assertEquals('Bridgestone YDS', $copiedTiresByCode['BG-YDS']->name);
        $this->assertEquals(15000, $copiedTiresByCode['BG-YDS']->price);

        $this->assertEquals('Vega XH3', $copiedTiresByCode['VG-XH3']->name);
        $this->assertEquals(12000, $copiedTiresByCode['VG-XH3']->price);

        $this->assertEquals('MG SM', $copiedTiresByCode['MG-SM']->name);
        $this->assertEquals(18000, $copiedTiresByCode['MG-SM']->price);
    }

    public function test_copied_tires_can_be_persisted_and_retrieved(): void
    {
        $sourceChampionship = Championship::factory()
            ->has(
                ChampionshipTire::factory()->state([
                    'name' => 'Test Tire',
                    'code' => 'TST-01',
                ]),
                'tires'
            )
            ->create();

        $targetChampionship = Championship::factory()->create();

        $copiedTires = (new CopyChampionshipTires)($sourceChampionship, $targetChampionship);

        $retrievedTire = ChampionshipTire::where('code', 'TST-01')
            ->where('championship_id', $targetChampionship->id)
            ->first();

        $this->assertNotNull($retrievedTire);
        $this->assertEquals('Test Tire', $retrievedTire->name);
        $this->assertTrue($retrievedTire->championship->is($targetChampionship));
    }

    public function test_existing_tires_in_target_championship_are_not_affected(): void
    {
        $sourceChampionship = Championship::factory()
            ->has(ChampionshipTire::factory()->count(2), 'tires')
            ->create();

        $targetChampionship = Championship::factory()
            ->has(
                ChampionshipTire::factory()->state([
                    'name' => 'Existing Tire',
                    'code' => 'EXIST-01',
                ]),
                'tires'
            )
            ->create();

        $existingTireId = $targetChampionship->tires->first()->id;

        $copiedTires = (new CopyChampionshipTires)($sourceChampionship, $targetChampionship);

        $targetChampionship->refresh();

        $this->assertCount(3, $targetChampionship->tires);

        $existingTire = $targetChampionship->tires->firstWhere('id', $existingTireId);
        $this->assertNotNull($existingTire);
        $this->assertEquals('Existing Tire', $existingTire->name);
        $this->assertEquals('EXIST-01', $existingTire->code);
    }
}

<?php

namespace Tests\Feature\Operations;

use App\Models\Category;
use App\Models\Championship;
use App\Models\ChampionshipTire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class BackfillChampionshipCategoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_championship_categories_backfilled_from_configuration()
    {
        config([
            'categories.default' => [
                'category_one' => [
                    'name' => 'CAT 1',
                    'tires' => 'VEGA_SL4',
                    'description' => 'A description',
                    'timekeeper_label' => 'ONE',
                ],
                'category_two' => [
                    'name' => 'CAT 2',
                    'tires' => 'VEGA_SL4',
                    'enabled' => false
                ],
            ],
        ]);
        
        $championship = Championship::factory()
            ->has(ChampionshipTire::factory()->count(2)->sequence(
                    ['name' => 'VEGA SL4', 'code' => 'VEGA_SL4', 'price' => 100],
                ), 'tires')
            ->create();

        $this->artisan("operations:process", [
                '--test' => true,
                '2023_12_31_125844_backfill_championship_categories',
            ])
            ->assertSuccessful();

        $categories = $championship->fresh()->categories;

        $this->assertCount(2, $categories);

        $firstCategory = $categories->first();
        $lastCategory = $categories->last();

        $expectedTire = ChampionshipTire::whereCode('VEGA_SL4')->firstOrFail();

        $this->assertEquals('category_one', $firstCategory->code);
        $this->assertEquals('ONE', $firstCategory->short_name);
        $this->assertEquals('CAT 1', $firstCategory->name);
        $this->assertTrue($firstCategory->enabled);
        $this->assertTrue($firstCategory->tire->is($expectedTire));
        
        $this->assertEquals('category_two', $lastCategory->code);
        $this->assertNull($lastCategory->short_name);
        $this->assertEquals('CAT 2', $lastCategory->name);
        $this->assertFalse($lastCategory->enabled);
        $this->assertTrue($lastCategory->tire->is($expectedTire));

        $this->assertEquals(2, $expectedTire->categories()->count());
    }
    
    public function test_championship_categories_not_backfilled_when_missing_tires()
    {
        config([
            // Tires must already exist in championship, otherwise skip
            'categories.default' => [
                'category_key' => [
                    'name' => 'CAT 1',
                    'tires' => 'MISSING_TIRE',
                ],
            ],
        ]);
        
        $championship = Championship::factory()
            ->create();

        $this->artisan("operations:process", [
                '--test' => true,
                '2023_12_31_125844_backfill_championship_categories',
            ])
            ->assertSuccessful();

        $categories = $championship->fresh()->categories;

        $this->assertCount(0, $categories);
    }

    public function test_backfill_only_done_when_no_categories_configured()
    {

        config([
            'categories.default' => [
                'category_key' => [
                    'name' => 'CAT 1',
                    'tires' => 'VEGA_SL4',
                ],
            ],
        ]);

        
        $championship = Championship::factory()
            ->has(Category::factory()->count(1), 'categories')
            ->create();

        $configuredCategory = $championship->categories()->first();
        
        $this->artisan("operations:process", [
                '--test' => true,
                '2023_12_31_125844_backfill_championship_categories',
            ])
            ->assertSuccessful();

        $categories = $championship->fresh()->categories;

        $this->assertCount(1, $categories);

        $this->assertTrue($configuredCategory->is($categories->first()));
    }
}

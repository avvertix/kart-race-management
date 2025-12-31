<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\CopyChampionshipCategories;
use App\Models\Category;
use App\Models\Championship;
use App\Models\ChampionshipTire;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class CopyChampionshipCategoriesTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_categories_are_copied_from_source_to_target_championship(): void
    {
        $sourceChampionship = Championship::factory()
            ->has(Category::factory()->count(3), 'categories')
            ->create();

        $targetChampionship = Championship::factory()->create();

        $copiedCategories = (new CopyChampionshipCategories)($sourceChampionship, $targetChampionship);

        $this->assertCount(3, $copiedCategories);
        $this->assertCount(3, $targetChampionship->fresh()->categories);
    }

    public function test_copied_categories_have_new_ulids(): void
    {
        $sourceChampionship = Championship::factory()
            ->has(Category::factory()->count(2), 'categories')
            ->create();

        $targetChampionship = Championship::factory()->create();

        $copiedCategories = (new CopyChampionshipCategories)($sourceChampionship, $targetChampionship);

        $sourceUlids = $sourceChampionship->categories->pluck('ulid')->toArray();
        $copiedUlids = $copiedCategories->pluck('ulid')->toArray();

        $this->assertNotEquals($sourceUlids, $copiedUlids);
        $this->assertCount(2, array_unique($copiedUlids));
    }

    public function test_copied_categories_belong_to_target_championship(): void
    {
        $sourceChampionship = Championship::factory()
            ->has(Category::factory()->count(2), 'categories')
            ->create();

        $targetChampionship = Championship::factory()->create();

        $copiedCategories = (new CopyChampionshipCategories)($sourceChampionship, $targetChampionship);

        $copiedCategories->each(function (Category $category) use ($targetChampionship) {
            $this->assertEquals($targetChampionship->id, $category->championship_id);
            $this->assertTrue($category->championship->is($targetChampionship));
        });
    }

    public function test_category_properties_are_copied_correctly(): void
    {
        $sourceChampionship = Championship::factory()
            ->has(
                Category::factory()->state([
                    'code' => 'MINI',
                    'name' => 'Minikart',
                    'description' => 'Entry level category',
                    'enabled' => true,
                    'short_name' => 'MK',
                ]),
                'categories'
            )
            ->create();

        $targetChampionship = Championship::factory()->create();

        $copiedCategories = (new CopyChampionshipCategories)($sourceChampionship, $targetChampionship);

        $this->assertCount(1, $copiedCategories);

        $copiedCategory = $copiedCategories->first();
        $sourceCategory = $sourceChampionship->categories->first();

        $this->assertEquals($sourceCategory->code, $copiedCategory->code);
        $this->assertEquals($sourceCategory->name, $copiedCategory->name);
        $this->assertEquals($sourceCategory->description, $copiedCategory->description);
        $this->assertEquals($sourceCategory->enabled, $copiedCategory->enabled);
        $this->assertEquals($sourceCategory->short_name, $copiedCategory->short_name);
    }

    public function test_categories_without_tires_are_copied(): void
    {
        $sourceChampionship = Championship::factory()
            ->has(
                Category::factory()->state([
                    'name' => 'No Tire Category',
                    'championship_tire_id' => null,
                ]),
                'categories'
            )
            ->create();

        $targetChampionship = Championship::factory()->create();

        $copiedCategories = (new CopyChampionshipCategories)($sourceChampionship, $targetChampionship);

        $this->assertCount(1, $copiedCategories);
        $this->assertNull($copiedCategories->first()->championship_tire_id);
    }

    public function test_category_with_existing_tire_in_target_uses_existing_tire(): void
    {
        // Create source championship with a tire
        $sourceChampionship = Championship::factory()->create();
        $sourceTire = $sourceChampionship->tires()->create([
            'name' => 'Bridgestone YDS',
            'code' => 'BG-YDS',
            'price' => 15000,
        ]);

        $sourceChampionship->categories()->create([
            'name' => 'Senior Category',
            'code' => 'SENIOR',
            'enabled' => true,
            'championship_tire_id' => $sourceTire->id,
        ]);

        // Create target championship with the same tire already existing
        $targetChampionship = Championship::factory()->create();
        $existingTargetTire = $targetChampionship->tires()->create([
            'name' => 'Bridgestone YDS',
            'code' => 'BG-YDS',
            'price' => 15000,
        ]);

        $copiedCategories = (new CopyChampionshipCategories)($sourceChampionship, $targetChampionship);

        $this->assertCount(1, $copiedCategories);
        $this->assertEquals($existingTargetTire->id, $copiedCategories->first()->championship_tire_id);
        $this->assertCount(1, $targetChampionship->fresh()->tires);
    }

    public function test_category_with_missing_tire_auto_creates_tire_in_target(): void
    {
        $sourceChampionship = Championship::factory()
            ->has(
                Category::factory()->withTireState([
                    'name' => 'Vega XH3',
                    'code' => 'VG-XH3',
                    'price' => 12000,
                ])->state([
                    'name' => 'Junior Category',
                ]),
                'categories'
            )
            ->create();

        $targetChampionship = Championship::factory()->create();

        $this->assertCount(0, $targetChampionship->tires);

        $copiedCategories = (new CopyChampionshipCategories)($sourceChampionship, $targetChampionship);

        $this->assertCount(1, $copiedCategories);
        $this->assertCount(1, $targetChampionship->fresh()->tires);

        $createdTire = $targetChampionship->fresh()->tires->first();
        $this->assertEquals('Vega XH3', $createdTire->name);
        $this->assertEquals('VG-XH3', $createdTire->code);
        $this->assertEquals(12000, $createdTire->price);

        $this->assertEquals($createdTire->id, $copiedCategories->first()->championship_tire_id);
    }

    public function test_multiple_categories_sharing_same_tire_only_create_tire_once(): void
    {
        $sourceChampionship = Championship::factory()->create();

        // Create a tire in source championship
        $sourceTire = $sourceChampionship->tires()->create([
            'name' => 'MG SM',
            'code' => 'MG-SM',
            'price' => 18000,
        ]);

        // Create multiple categories using the same tire
        $sourceChampionship->categories()->create([
            'name' => 'Category A',
            'code' => 'CAT-A',
            'enabled' => true,
            'championship_tire_id' => $sourceTire->id,
        ]);

        $sourceChampionship->categories()->create([
            'name' => 'Category B',
            'code' => 'CAT-B',
            'enabled' => true,
            'championship_tire_id' => $sourceTire->id,
        ]);

        $sourceChampionship->categories()->create([
            'name' => 'Category C',
            'code' => 'CAT-C',
            'enabled' => true,
            'championship_tire_id' => $sourceTire->id,
        ]);

        $targetChampionship = Championship::factory()->create();

        $copiedCategories = (new CopyChampionshipCategories)($sourceChampionship, $targetChampionship);

        $this->assertCount(3, $copiedCategories);
        $this->assertCount(1, $targetChampionship->fresh()->tires);

        $targetTire = $targetChampionship->fresh()->tires->first();
        $this->assertEquals('MG SM', $targetTire->name);
        $this->assertEquals('MG-SM', $targetTire->code);

        // All categories should reference the same tire
        $copiedCategories->each(function (Category $category) use ($targetTire) {
            $this->assertEquals($targetTire->id, $category->championship_tire_id);
        });
    }

    public function test_source_categories_remain_unchanged(): void
    {
        $sourceChampionship = Championship::factory()
            ->has(Category::factory()->count(2), 'categories')
            ->create();

        $originalSourceCategoriesCount = $sourceChampionship->categories->count();
        $originalSourceCategoryIds = $sourceChampionship->categories->pluck('id')->toArray();

        $targetChampionship = Championship::factory()->create();

        (new CopyChampionshipCategories)($sourceChampionship, $targetChampionship);

        $sourceChampionship->refresh();

        $this->assertCount($originalSourceCategoriesCount, $sourceChampionship->categories);
        $this->assertEquals($originalSourceCategoryIds, $sourceChampionship->categories->pluck('id')->toArray());
    }

    public function test_returns_empty_collection_when_source_has_no_categories(): void
    {
        $sourceChampionship = Championship::factory()->create();
        $targetChampionship = Championship::factory()->create();

        $copiedCategories = (new CopyChampionshipCategories)($sourceChampionship, $targetChampionship);

        $this->assertCount(0, $copiedCategories);
        $this->assertCount(0, $targetChampionship->fresh()->categories);
    }

    public function test_disabled_categories_are_copied_with_disabled_state(): void
    {
        $sourceChampionship = Championship::factory()
            ->has(
                Category::factory()->disabled()->state([
                    'name' => 'Disabled Category',
                ]),
                'categories'
            )
            ->create();

        $targetChampionship = Championship::factory()->create();

        $copiedCategories = (new CopyChampionshipCategories)($sourceChampionship, $targetChampionship);

        $this->assertCount(1, $copiedCategories);
        $this->assertFalse($copiedCategories->first()->enabled);
    }

    public function test_existing_categories_in_target_championship_are_not_affected(): void
    {
        $sourceChampionship = Championship::factory()
            ->has(Category::factory()->count(2), 'categories')
            ->create();

        $targetChampionship = Championship::factory()
            ->has(
                Category::factory()->state([
                    'name' => 'Existing Category',
                    'code' => 'EXIST-01',
                ]),
                'categories'
            )
            ->create();

        $existingCategoryId = $targetChampionship->categories->first()->id;

        $copiedCategories = (new CopyChampionshipCategories)($sourceChampionship, $targetChampionship);

        $targetChampionship->refresh();

        $this->assertCount(3, $targetChampionship->categories);

        $existingCategory = $targetChampionship->categories->firstWhere('id', $existingCategoryId);
        $this->assertNotNull($existingCategory);
        $this->assertEquals('Existing Category', $existingCategory->name);
        $this->assertEquals('EXIST-01', $existingCategory->code);
    }

    public function test_copied_categories_can_be_persisted_and_retrieved(): void
    {
        $sourceChampionship = Championship::factory()
            ->has(
                Category::factory()->state([
                    'name' => 'Test Category',
                    'code' => 'TST-CAT',
                ]),
                'categories'
            )
            ->create();

        $targetChampionship = Championship::factory()->create();

        $copiedCategories = (new CopyChampionshipCategories)($sourceChampionship, $targetChampionship);

        $retrievedCategory = Category::where('code', 'TST-CAT')
            ->where('championship_id', $targetChampionship->id)
            ->first();

        $this->assertNotNull($retrievedCategory);
        $this->assertEquals('Test Category', $retrievedCategory->name);
        $this->assertTrue($retrievedCategory->championship->is($targetChampionship));
    }

    public function test_mixed_categories_with_and_without_tires_are_copied(): void
    {
        $sourceChampionship = Championship::factory()->create();

        // Category without tire
        $sourceChampionship->categories()->create([
            'name' => 'No Tire',
            'code' => 'NO-TIRE',
            'enabled' => true,
            'championship_tire_id' => null,
        ]);

        // Category with tire
        $tire = $sourceChampionship->tires()->create([
            'name' => 'Test Tire',
            'code' => 'TEST-TIRE',
            'price' => 10000,
        ]);

        $sourceChampionship->categories()->create([
            'name' => 'With Tire',
            'code' => 'WITH-TIRE',
            'enabled' => true,
            'championship_tire_id' => $tire->id,
        ]);

        $targetChampionship = Championship::factory()->create();

        $copiedCategories = (new CopyChampionshipCategories)($sourceChampionship, $targetChampionship);

        $this->assertCount(2, $copiedCategories);

        $categoryWithoutTire = $copiedCategories->firstWhere('code', 'NO-TIRE');
        $categoryWithTire = $copiedCategories->firstWhere('code', 'WITH-TIRE');

        $this->assertNull($categoryWithoutTire->championship_tire_id);
        $this->assertNotNull($categoryWithTire->championship_tire_id);

        $this->assertCount(1, $targetChampionship->fresh()->tires);
    }

    public function test_auto_created_tires_have_unique_ulids(): void
    {
        $sourceChampionship = Championship::factory()
            ->has(
                Category::factory()->withTireState([
                    'name' => 'Tire A',
                    'code' => 'TIRE-A',
                    'price' => 10000,
                ]),
                'categories'
            )
            ->has(
                Category::factory()->withTireState([
                    'name' => 'Tire B',
                    'code' => 'TIRE-B',
                    'price' => 12000,
                ]),
                'categories'
            )
            ->create();

        $targetChampionship = Championship::factory()->create();

        $copiedCategories = (new CopyChampionshipCategories)($sourceChampionship, $targetChampionship);

        $this->assertCount(2, $copiedCategories);
        $this->assertCount(2, $targetChampionship->fresh()->tires);

        $createdTires = $targetChampionship->fresh()->tires;
        $ulids = $createdTires->pluck('ulid')->toArray();

        $this->assertCount(2, array_unique($ulids));
    }
}

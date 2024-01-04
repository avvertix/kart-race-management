<?php

namespace Tests\Feature\Operations;

use App\Models\Category;
use App\Models\Championship;
use App\Models\ChampionshipTire;
use App\Models\Participant;
use App\Models\Race;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class BackfillParticipantCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_participant_category_backfilled()
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
            ->has(ChampionshipTire::factory()->count(1)->sequence(
                    ['name' => 'VEGA SL4', 'code' => 'VEGA_SL4', 'price' => 100],
                ), 'tires')
            ->has(Category::factory()->count(1)->sequence(
                ['name' => 'CAT 1', 'code' => 'category_one']
            ), 'categories')
            ->create();

        $race = Race::factory()->create();
    
        $participant = Participant::factory()
            ->recycle($championship)
            ->recycle($race)
            ->create([
                'category' => 'category_one',
                'bib' => 100,
            ]);

        $this->artisan("operations:process", [
                '--test' => true,
                'name' => '2024_01_03_082636_backfill_participant_category',
            ])
            ->assertSuccessful();

        $updatedParticipant = $participant->fresh();

        $this->assertInstanceOf(Category::class, $updatedParticipant->racingCategory);

        $category = $updatedParticipant->racingCategory;

        $this->assertTrue($category->is($championship->categories()->first()));
    }
    
    public function test_participant_category_not_backfilled_when_missing_category()
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
            ->has(ChampionshipTire::factory()->count(1)->sequence(
                    ['name' => 'VEGA SL4', 'code' => 'VEGA_SL4', 'price' => 100],
                ), 'tires')
            ->create();
        
        $race = Race::factory()->create();
    
        $participant = Participant::factory()
            ->recycle($championship)
            ->recycle($race)
            ->create([
                'category' => 'category_one',
                'bib' => 100,
            ]);

        $this->artisan("operations:process", [
                '--test' => true,
                'name' => '2024_01_03_082636_backfill_participant_category',
            ])
            ->assertSuccessful();

        $updatedParticipant = $participant->fresh();

        $this->assertNull($updatedParticipant->racingCategory);
    }

    public function test_participant_category_not_backfilled_if_already_set()
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
            ->has(ChampionshipTire::factory()->count(1)->sequence(
                    ['name' => 'VEGA SL4', 'code' => 'VEGA_SL4', 'price' => 100],
                ), 'tires')
            ->has(Category::factory()->count(1)->sequence(
                ['name' => 'CAT 1', 'code' => 'category_one']
            ), 'categories')
            ->create();

        $race = Race::factory()->create();
    
        $participant = Participant::factory()
            ->recycle($championship)
            ->recycle($race)
            ->category($championship->categories()->first())
            ->create([
                'bib' => 100,
            ]);

        $this->artisan("operations:process", [
                '--test' => true,
                'name' => '2024_01_03_082636_backfill_participant_category',
            ])
            ->assertSuccessful();

        $updatedParticipant = $participant->fresh();

        $this->assertInstanceOf(Category::class, $updatedParticipant->racingCategory);

        $category = $updatedParticipant->racingCategory;

        $this->assertTrue($category->is($championship->categories()->first()));
        
    }
}

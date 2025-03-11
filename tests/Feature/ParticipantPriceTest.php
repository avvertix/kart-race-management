<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Championship;
use App\Models\Participant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ParticipantPriceTest extends TestCase
{
    use RefreshDatabase;

    public function test_participant_price()
    {
        $category = Category::factory()
            ->withTireState([
                'name' => 'T1',
                'price' => 10,
            ])
            ->create([
                'name' => 'CAT 1',
            ]);

        $price = Participant::factory()->category($category)->create()->price();

        $this->assertInstanceOf(Collection::class, $price);

        $this->assertEquals([
            __('Race fee') => 15000,
            __('Tires (:tire)', ['tire' => 'T1']) => 10,
            __('Total') => 15010,
        ], $price->toArray());
    }

    public function test_participant_price_consider_bonus()
    {
        $category = Category::factory()
            ->withTireState([
                'name' => 'T1',
                'price' => 10,
            ])
            ->create([
                'name' => 'CAT 1',
            ]);

        $price = Participant::factory()->category($category)->usingBonus()->create()->price();

        $this->assertInstanceOf(Collection::class, $price);

        $this->assertEquals([
            __('Race fee') => 15000,
            __('Tires (:tire)', ['tire' => 'T1']) => 10,
            __('Bonus') => -15000,
            __('Total') => 10,
        ], $price->toArray());
    }

    public function test_category_without_tire()
    {
        $category = Category::factory()
            ->create([
                'name' => 'CAT 1',
            ]);

        $price = Participant::factory()->category($category)->create()->price();

        $this->assertInstanceOf(Collection::class, $price);

        $this->assertEquals([
            __('Race fee') => 15000,
            __('Total') => 15000,
        ], $price->toArray());
    }

    public function test_championship_race_price_used()
    {
        $championship = Championship::factory()->priced(12000)->create();

        $category = Category::factory()
            ->create([
                'name' => 'CAT 1',
            ]);

        $price = Participant::factory()
            ->recycle($championship)
            ->category($category)
            ->create()
            ->price();

        $this->assertInstanceOf(Collection::class, $price);

        $this->assertEquals([
            __('Race fee') => 12000,
            __('Total') => 12000,
        ], $price->toArray());
    }
}

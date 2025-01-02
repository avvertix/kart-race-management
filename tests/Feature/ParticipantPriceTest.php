<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Participant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ParticipantPriceTest extends TestCase
{
    use RefreshDatabase;

    public function test_participant_price()
    {
        $category = $this->setAvailableCategories();

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
        $category = $this->setAvailableCategories();

        $price = Participant::factory()->category($category)->usingBonus()->create()->price();

        $this->assertInstanceOf(Collection::class, $price);

        $this->assertEquals([
            __('Race fee') => 15000,
            __('Tires (:tire)', ['tire' => 'T1']) => 10,
            __('Bonus') => -15000,
            __('Total') => 10,
        ], $price->toArray());
    }

    protected function setAvailableCategories(): Category
    {
        return Category::factory()
            ->withTireState([
                'name' => 'T1',
                'price' => 10,
            ])
            ->create([
                'name' => 'CAT 1',
            ]);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Data\RegistrationCostData;
use App\Models\Category;
use App\Models\Championship;
use App\Models\Participant;
use Illuminate\Support\Collection;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class ParticipantPriceTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_participant_price()
    {
        config([
            'races.price' => 15000,
            'races.bonus_amount' => 0,
        ]);

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

    public function test_participant_price_returned_from_saved_cost()
    {
        $price = Participant::factory()
            ->create([
                'cost' => new RegistrationCostData(
                    registration_cost: 15000,
                    tire_cost: 10,
                    tire_model: 'T1',
                    discount: 5000,
                ),
            ])
            ->price();

        $this->assertInstanceOf(Collection::class, $price);

        $this->assertEquals([
            __('Race fee') => 15000,
            __('Tires (:tire)', ['tire' => 'T1']) => 10,
            __('Discount') => -5000,
            __('Total') => 10010,
        ], $price->toArray());
    }

    public function test_participant_price_consider_bonus()
    {
        config([
            'races.price' => 15000,
            'races.bonus_amount' => 15000,
        ]);

        $championship = Championship::factory()->create();

        $category = Category::factory()
            ->recycle($championship)
            ->withTireState([
                'name' => 'T1',
                'price' => 10,
            ])
            ->create([
                'name' => 'CAT 1',
            ]);

        $price = Participant::factory()
            ->recycle($championship)
            ->category($category)
            ->usingCredits()
            ->create()
            ->price();

        $this->assertInstanceOf(Collection::class, $price);

        $this->assertEquals([
            __('Race fee') => 15000,
            __('Tires (:tire)', ['tire' => 'T1']) => 10,
            __('Discount') => -15000,
            __('Total') => 10,
        ], $price->toArray());
    }

    public function test_category_without_tire()
    {
        $category = Category::factory()
            ->create([
                'name' => 'CAT 1',
            ]);

        $price = Participant::factory()
            ->category($category)
            ->create()
            ->price();

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

    public function test_championship_credit_discount_used()
    {
        $championship = Championship::factory()
            ->priced(12000)
            ->withCredits(10000)
            ->create();

        $category = Category::factory()
            ->create([
                'name' => 'CAT 1',
            ]);

        $price = Participant::factory()
            ->recycle($championship)
            ->category($category)
            ->usingCredits()
            ->create()
            ->price();

        $this->assertInstanceOf(Collection::class, $price);

        $this->assertEquals([
            __('Race fee') => 12000,
            __('Discount') => -10000,
            __('Total') => 2000,
        ], $price->toArray());
    }

    public function test_championship_wallet_discount_used()
    {
        $championship = Championship::factory()
            ->priced(12000)
            ->withBalanceBonus()
            ->create();

        $category = Category::factory()
            ->create([
                'name' => 'CAT 1',
            ]);

        $price = Participant::factory()
            ->recycle($championship)
            ->category($category)
            ->usingBalance(available_amount: 20000, used_amount: 12000)
            ->create()
            ->price();

        $this->assertInstanceOf(Collection::class, $price);

        $this->assertEquals([
            __('Race fee') => 12000,
            __('Discount') => -12000,
            __('Total') => 0,
        ], $price->toArray());
    }

    public function test_price_shown_when_zero()
    {
        $championship = Championship::factory()
            ->priced(0)
            ->create();

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
            __('Race fee') => 0,
            __('Total') => 0,
        ], $price->toArray());
    }
}

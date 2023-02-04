<?php

namespace Tests\Feature;

use App\Models\Participant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ParticipantPriceTest extends TestCase
{
    use RefreshDatabase;

    protected function setAvailableCategories()
    {
        config([
            'categories.default' => [
                'category_key' => [
                    'name' => 'CAT 1',
                    'tires' => 'T1',
                ],
            ],
            'races.tires' => [
                'T1' => [
                    'name' => 'T1',
                    'price' => 10,
                ],
            ],
        ]);
    }

    public function test_participant_price_()
    {
        $this->setAvailableCategories();

        $price = Participant::factory()->make()->price();

        $this->assertInstanceOf(Collection::class, $price);

        $this->assertEquals([
            __("Race fee") => 15000,
            __("Tires (:tire)", ['tire' => 'T1']) => 10,
            __("Total") => 15010,
        ], $price->toArray());
    }
    
    public function test_participant_price_consider_bonus()
    {
        $this->setAvailableCategories();

        $price = Participant::factory()->usingBonus()->make()->price();

        $this->assertInstanceOf(Collection::class, $price);

        $this->assertEquals([
            __("Race fee") => 15000,
            __("Tires (:tire)", ['tire' => 'T1']) => 10,
            __("Bonus") => -15000,
            __("Total") => 10,
        ], $price->toArray());
    }
}

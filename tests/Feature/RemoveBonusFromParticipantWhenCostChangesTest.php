<?php

namespace Tests\Feature;

use App\Events\ParticipantUpdated;
use App\Listeners\RemoveBonusFromParticipantWhenCostChanges;
use App\Models\Bonus;
use App\Models\Category;
use App\Models\Championship;
use App\Models\Participant;
use App\Models\Race;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RemoveBonusFromParticipantWhenCostChangesTest extends TestCase
{
    use FastRefreshDatabase;

    
    public function test_bonus_removed_after_category_change(): void
    {
        config([
            'races.price' => 15000,
            'races.bonus_amount' => 0,
        ]);

        $championship = Championship::factory()->withBalanceBonus()->create();

        $race = Race::factory()->recycle($championship)->create();

        $category_with_price = Category::factory()->recycle($championship)->withPrice(5000)->create();
        
        $category = Category::factory()->recycle($championship)->create();

        $bonus = Bonus::factory()->recycle($race->championship)->create([
            'driver_licence' => 'D0001',
            'driver_licence_hash' => hash('sha512', 'D0001'),
            'amount' => 10000,
        ]);

        $participant = Participant::factory()
            ->for($race)
            ->for($championship)
            ->category($category_with_price)
            ->usingBalance(bonus: $bonus, used_amount: 5000)
            ->driver([
                'first_name' => 'John',
                'last_name' => 'Racer',
                'licence_number' => 'D0001',
                'bib' => 100,
            ])
            ->create();

        // change category

        $participant->update([
            'category_id' => $category->getKey(),
        ]);

        $event = new ParticipantUpdated($participant->fresh(), $race);

        (new RemoveBonusFromParticipantWhenCostChanges())->handle($event, function ($event) {
            return $event;
        });

        $participant = $participant->fresh();

        $this->assertFalse($participant->use_bonus);
        $this->assertEquals(0, $participant->bonuses->count());
    }
}

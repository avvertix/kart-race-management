<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\CalculateParticipationCost;
use App\Data\RegistrationCostData;
use App\Events\ParticipantRegistered;
use App\Events\ParticipantUpdated;
use App\Models\Category;
use App\Models\Championship;
use App\Models\Participant;
use App\Models\Race;
use Illuminate\Validation\ValidationException;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class CalculateParticipationCostTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_cost_calculated_on_participant_registration()
    {
        config([
            'races.price' => 15000,
        ]);

        $race = Race::factory()->create();

        $category = Category::factory()->recycle($race->championship)->create();

        $participant = Participant::factory()
            ->for($race)
            ->for($race->championship)
            ->category($category)
            ->driver([
                'first_name' => 'John',
                'last_name' => 'Racer',
                'licence_number' => 'D0001',
                'bib' => 100,
            ])
            ->create();

        $this->assertNull($participant->cost);

        $event = new ParticipantRegistered($participant, $race);

        (new CalculateParticipationCost())->handle($event, function ($event) {
            return $event;
        });

        $participant->refresh();

        $this->assertInstanceOf(RegistrationCostData::class, $participant->cost);
        $this->assertEquals(15000, $participant->cost->registration_cost);
    }

    public function test_cost_calculated_on_participant_update()
    {
        config([
            'races.price' => 15000,
        ]);

        $race = Race::factory()->create();

        $category = Category::factory()->recycle($race->championship)->create();

        $participant = Participant::factory()
            ->for($race)
            ->for($race->championship)
            ->category($category)
            ->driver([
                'first_name' => 'John',
                'last_name' => 'Racer',
                'licence_number' => 'D0001',
                'bib' => 100,
            ])
            ->create();

        $event = new ParticipantUpdated($participant, $race);

        (new CalculateParticipationCost())->handle($event, function ($event) {
            return $event;
        });

        $participant->refresh();

        $this->assertInstanceOf(RegistrationCostData::class, $participant->cost);
        $this->assertEquals(15000, $participant->cost->registration_cost);
    }

    public function test_cost_uses_category_registration_price()
    {
        config([
            'races.price' => 15000,
        ]);

        $race = Race::factory()->create();

        $category = Category::factory()
            ->recycle($race->championship)
            ->create([
                'registration_price' => 12000,
            ]);

        $participant = Participant::factory()
            ->for($race)
            ->for($race->championship)
            ->category($category)
            ->driver([
                'first_name' => 'John',
                'last_name' => 'Racer',
                'licence_number' => 'D0001',
                'bib' => 100,
            ])
            ->create();

        $event = new ParticipantRegistered($participant, $race);

        (new CalculateParticipationCost())->handle($event, function ($event) {
            return $event;
        });

        $participant->refresh();

        $this->assertEquals(12000, $participant->cost->registration_cost);
    }

    public function test_cost_updated_when_category_changed_to_different_registration_price()
    {
        config([
            'races.price' => 15000,
        ]);

        $race = Race::factory()->create();

        $categoryA = Category::factory()
            ->recycle($race->championship)
            ->create([
                'name' => 'Category A',
                'registration_price' => 10000,
            ]);

        $categoryB = Category::factory()
            ->recycle($race->championship)
            ->create([
                'name' => 'Category B',
                'registration_price' => 20000,
            ]);

        $participant = Participant::factory()
            ->for($race)
            ->for($race->championship)
            ->category($categoryA)
            ->driver([
                'first_name' => 'John',
                'last_name' => 'Racer',
                'licence_number' => 'D0001',
                'bib' => 100,
            ])
            ->create();

        $event = new ParticipantRegistered($participant, $race);

        (new CalculateParticipationCost())->handle($event, function ($event) {
            return $event;
        });

        $participant->refresh();

        $this->assertEquals(10000, $participant->cost->registration_cost);

        // Change category
        $participant->category_id = $categoryB->id;
        $participant->save();
        $participant->refresh();

        $updateEvent = new ParticipantUpdated($participant, $race);

        (new CalculateParticipationCost())->handle($updateEvent, function ($event) {
            return $event;
        });

        $participant->refresh();

        $this->assertEquals(20000, $participant->cost->registration_cost);
    }

    public function test_cost_updated_when_category_changed_from_priced_to_default()
    {
        config([
            'races.price' => 15000,
        ]);

        $race = Race::factory()->create();

        $categoryWithPrice = Category::factory()
            ->recycle($race->championship)
            ->create([
                'name' => 'Premium Category',
                'registration_price' => 25000,
            ]);

        $categoryWithoutPrice = Category::factory()
            ->recycle($race->championship)
            ->create([
                'name' => 'Standard Category',
                'registration_price' => null,
            ]);

        $participant = Participant::factory()
            ->for($race)
            ->for($race->championship)
            ->category($categoryWithPrice)
            ->driver([
                'first_name' => 'John',
                'last_name' => 'Racer',
                'licence_number' => 'D0001',
                'bib' => 100,
            ])
            ->create();

        $event = new ParticipantRegistered($participant, $race);

        (new CalculateParticipationCost())->handle($event, function ($event) {
            return $event;
        });

        $participant->refresh();

        $this->assertEquals(25000, $participant->cost->registration_cost);

        // Change to category without specific price
        $participant->category_id = $categoryWithoutPrice->id;
        $participant->save();
        $participant->refresh();

        $updateEvent = new ParticipantUpdated($participant, $race);

        (new CalculateParticipationCost())->handle($updateEvent, function ($event) {
            return $event;
        });

        $participant->refresh();

        // Should fall back to config default
        $this->assertEquals(15000, $participant->cost->registration_cost);
    }

    public function test_cost_includes_tire_price()
    {
        config([
            'races.price' => 15000,
        ]);

        $race = Race::factory()->create();

        $category = Category::factory()
            ->recycle($race->championship)
            ->withTireState([
                'name' => 'Premium Tire',
                'price' => 5000,
            ])
            ->create();

        $participant = Participant::factory()
            ->for($race)
            ->for($race->championship)
            ->category($category)
            ->driver([
                'first_name' => 'John',
                'last_name' => 'Racer',
                'licence_number' => 'D0001',
                'bib' => 100,
            ])
            ->create();

        $event = new ParticipantRegistered($participant, $race);

        (new CalculateParticipationCost())->handle($event, function ($event) {
            return $event;
        });

        $participant->refresh();

        $this->assertEquals(15000, $participant->cost->registration_cost);
        $this->assertEquals(5000, $participant->cost->tire_cost);
        $this->assertEquals('Premium Tire', $participant->cost->tire_model);
    }

    public function test_cost_uses_championship_registration_price_when_category_has_none()
    {
        $championship = Championship::factory()->priced(18000)->create();

        $race = Race::factory()->recycle($championship)->create();

        $category = Category::factory()
            ->recycle($championship)
            ->create([
                'registration_price' => null,
            ]);

        $participant = Participant::factory()
            ->for($race)
            ->for($championship)
            ->category($category)
            ->driver([
                'first_name' => 'John',
                'last_name' => 'Racer',
                'licence_number' => 'D0001',
                'bib' => 100,
            ])
            ->create();

        $event = new ParticipantRegistered($participant, $race);

        (new CalculateParticipationCost())->handle($event, function ($event) {
            return $event;
        });

        $participant->refresh();

        $this->assertEquals(18000, $participant->cost->registration_cost);
    }

    public function test_category_price_takes_precedence_over_championship_price()
    {
        $championship = Championship::factory()->priced(18000)->create();

        $race = Race::factory()->recycle($championship)->create();

        $category = Category::factory()
            ->recycle($championship)
            ->create([
                'registration_price' => 12000,
            ]);

        $participant = Participant::factory()
            ->for($race)
            ->for($championship)
            ->category($category)
            ->driver([
                'first_name' => 'John',
                'last_name' => 'Racer',
                'licence_number' => 'D0001',
                'bib' => 100,
            ])
            ->create();

        $event = new ParticipantRegistered($participant, $race);

        (new CalculateParticipationCost())->handle($event, function ($event) {
            return $event;
        });

        $participant->refresh();

        // Category price should take precedence
        $this->assertEquals(12000, $participant->cost->registration_cost);
    }

    public function test_throws_exception_when_race_is_cancelled()
    {
        $race = Race::factory()->cancelled()->create();

        $category = Category::factory()->recycle($race->championship)->create();

        $participant = Participant::factory()
            ->for($race)
            ->for($race->championship)
            ->category($category)
            ->driver([
                'first_name' => 'John',
                'last_name' => 'Racer',
                'licence_number' => 'D0001',
                'bib' => 100,
            ])
            ->create();

        $event = new ParticipantRegistered($participant, $race);

        $this->expectException(ValidationException::class);

        (new CalculateParticipationCost())->handle($event, function ($event) {
            return $event;
        });
    }

    public function test_cost_recalculated_when_already_set()
    {
        config([
            'races.price' => 15000,
        ]);

        $race = Race::factory()->create();

        $category = Category::factory()
            ->recycle($race->championship)
            ->create([
                'registration_price' => 20000,
            ]);

        $participant = Participant::factory()
            ->for($race)
            ->for($race->championship)
            ->category($category)
            ->driver([
                'first_name' => 'John',
                'last_name' => 'Racer',
                'licence_number' => 'D0001',
                'bib' => 100,
            ])
            ->create([
                'cost' => new RegistrationCostData(
                    registration_cost: 10000,
                    tire_cost: 0,
                    tire_model: null,
                    discount: 0,
                ),
            ]);

        $this->assertEquals(10000, $participant->cost->registration_cost);

        $event = new ParticipantUpdated($participant, $race);

        (new CalculateParticipationCost())->handle($event, function ($event) {
            return $event;
        });

        $participant->refresh();

        // Cost should be recalculated based on current category
        $this->assertEquals(20000, $participant->cost->registration_cost);
    }
}

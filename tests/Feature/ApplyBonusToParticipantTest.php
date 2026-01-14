<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Events\ParticipantRegistered;
use App\Listeners\ApplyBonusToParticipant;
use App\Models\Bonus;
use App\Models\Category;
use App\Models\Championship;
use App\Models\Participant;
use App\Models\Race;
use Illuminate\Support\Facades\DB;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class ApplyBonusToParticipantTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_bonus_not_applied_twice()
    {
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

        $bonus = Bonus::factory()
            ->recycle($race->championship)

            ->create([
                'driver_licence' => 'D0001',
                'driver_licence_hash' => hash('sha512', 'D0001'),
                'amount' => 2,
            ]);

        $bonus->usages()->attach($participant);

        $event = new ParticipantRegistered($participant, $race);

        (new ApplyBonusToParticipant())->handle($event, function ($event) {
            return $event;
        });

        $this->assertEquals(1, $participant->bonuses()->count());

    }

    public function test_bonus_not_applied_when_national_race()
    {
        $race = Race::factory()->national()->create();

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

        (new ApplyBonusToParticipant())->handle($event, function ($event) {
            return $event;
        });

        $this->assertEquals(0, $participant->bonuses()->count());

    }

    public function test_bonus_not_applied_when_international_race()
    {
        $race = Race::factory()->international()->create();

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

        (new ApplyBonusToParticipant())->handle($event, function ($event) {
            return $event;
        });

        $this->assertEquals(0, $participant->bonuses()->count());

    }

    public function test_credit_bonus_used()
    {
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

        $bonus = Bonus::factory()->recycle($race->championship)->create([
            'driver_licence' => 'D0001',
            'driver_licence_hash' => hash('sha512', 'D0001'),
            'amount' => 1,
        ]);

        $event = new ParticipantRegistered($participant, $race);

        (new ApplyBonusToParticipant())->handle($event, function ($event) {
            return $event;
        });

        $participant_bonuses = $participant->bonuses;

        $this->assertEquals(1, $participant_bonuses->count());
        $this->assertEquals(config('races.bonus_amount'), $participant_bonuses->first()->pivot->amount);
    }

    public function test_balance_bonus_used()
    {
        $championship = Championship::factory()->withBalanceBonus()->create();

        $race = Race::factory()->recycle($championship)->create();

        $category = Category::factory()->recycle($championship)->withPrice(5000)->create();

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

        $bonus = Bonus::factory()->recycle($race->championship)->create([
            'driver_licence' => 'D0001',
            'driver_licence_hash' => hash('sha512', 'D0001'),
            'amount' => 10000,
        ]);

        $event = new ParticipantRegistered($participant, $race);

        (new ApplyBonusToParticipant())->handle($event, function ($event) {
            return $event;
        });

        $participant_bonuses = $participant->bonuses;

        $this->assertEquals(1, $participant_bonuses->count());
        $this->assertEquals(5000, $participant_bonuses->first()->pivot->amount);

        $freshBonus = $bonus->fresh();

        $this->assertEquals(5000, $freshBonus->remaining);
    }

    public function test_balance_bonus_used_using_sum_load()
    {
        $championship = Championship::factory()->withBalanceBonus()->create();

        $race = Race::factory()->recycle($championship)->create();

        $category = Category::factory()->recycle($championship)->withPrice(5000)->create();

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

        $bonus = Bonus::factory()->recycle($race->championship)->create([
            'driver_licence' => 'D0001',
            'driver_licence_hash' => hash('sha512', 'D0001'),
            'amount' => 10000,
        ]);

        $event = new ParticipantRegistered($participant, $race);

        (new ApplyBonusToParticipant())->handle($event, function ($event) {
            return $event;
        });

        $participant_bonuses = $participant->bonuses;

        $this->assertEquals(1, $participant_bonuses->count());
        $this->assertEquals(5000, $participant_bonuses->first()->pivot->amount);

        $freshBonus = $bonus->fresh()->loadSum(['usages as used_amount' => function ($query) {
            $query->select(DB::raw('sum(amount)'));
        }], 'used_amount');

        $this->assertEquals(5000, $freshBonus->remaining);
    }
}

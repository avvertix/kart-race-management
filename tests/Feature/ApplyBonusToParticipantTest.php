<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Events\ParticipantRegistered;
use App\Listeners\ApplyBonusToParticipant;
use App\Models\Bonus;
use App\Models\Category;
use App\Models\Participant;
use App\Models\Race;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplyBonusToParticipantTest extends TestCase
{
    use RefreshDatabase;

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

        (new ApplyBonusToParticipant())->handle($event);

        $this->assertEquals(1, $participant->bonuses()->count());

    }
}

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
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class BonusEnabledTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_bonus_not_applied_when_race_bonus_disabled(): void
    {
        $race = Race::factory()->create(['bonus_enabled' => false]);
        $category = Category::factory()->recycle($race->championship)->create();
        $participant = Participant::factory()
            ->for($race)
            ->for($race->championship)
            ->category($category)
            ->driver(['licence_number' => 'D0001', 'bib' => 100])
            ->create();

        Bonus::factory()
            ->recycle($race->championship)
            ->create([
                'driver_licence' => 'D0001',
                'driver_licence_hash' => hash('sha512', 'D0001'),
                'amount' => 2,
            ]);

        $event = new ParticipantRegistered($participant, $race);

        (new ApplyBonusToParticipant())->handle($event, fn ($e) => $e);

        $this->assertEquals(0, $participant->bonuses()->count());
    }

    public function test_bonus_applied_when_race_bonus_enabled(): void
    {
        $race = Race::factory()->create(['bonus_enabled' => true]);
        $category = Category::factory()->recycle($race->championship)->create();
        $participant = Participant::factory()
            ->for($race)
            ->for($race->championship)
            ->category($category)
            ->driver(['licence_number' => 'D0001', 'bib' => 100])
            ->create();

        Bonus::factory()
            ->recycle($race->championship)
            ->create([
                'driver_licence' => 'D0001',
                'driver_licence_hash' => hash('sha512', 'D0001'),
                'amount' => 2,
            ]);

        $event = new ParticipantRegistered($participant, $race);

        (new ApplyBonusToParticipant())->handle($event, fn ($e) => $e);

        $this->assertEquals(1, $participant->bonuses()->count());
    }

    public function test_bonus_not_applied_when_championship_bonus_disabled_and_race_not_set(): void
    {
        $championship = Championship::factory()->create(['bonus_enabled' => false]);
        $race = Race::factory()->for($championship)->create(['bonus_enabled' => null]);
        $category = Category::factory()->recycle($championship)->create();
        $participant = Participant::factory()
            ->for($race)
            ->for($championship)
            ->category($category)
            ->driver(['licence_number' => 'D0001', 'bib' => 100])
            ->create();

        Bonus::factory()
            ->recycle($championship)
            ->create([
                'driver_licence' => 'D0001',
                'driver_licence_hash' => hash('sha512', 'D0001'),
                'amount' => 2,
            ]);

        $race->load('championship');

        $event = new ParticipantRegistered($participant, $race);

        (new ApplyBonusToParticipant())->handle($event, fn ($e) => $e);

        $this->assertEquals(0, $participant->bonuses()->count());
    }

    public function test_race_bonus_enabled_overrides_championship_disabled(): void
    {
        $championship = Championship::factory()->create(['bonus_enabled' => false]);
        $race = Race::factory()->for($championship)->create(['bonus_enabled' => true]);
        $category = Category::factory()->recycle($championship)->create();
        $participant = Participant::factory()
            ->for($race)
            ->for($championship)
            ->category($category)
            ->driver(['licence_number' => 'D0001', 'bib' => 100])
            ->create();

        Bonus::factory()
            ->recycle($championship)
            ->create([
                'driver_licence' => 'D0001',
                'driver_licence_hash' => hash('sha512', 'D0001'),
                'amount' => 2,
            ]);

        $race->load('championship');

        $event = new ParticipantRegistered($participant, $race);

        (new ApplyBonusToParticipant())->handle($event, fn ($e) => $e);

        $this->assertEquals(1, $participant->bonuses()->count());
    }

    public function test_is_bonus_enabled_defaults_to_true_when_not_set(): void
    {
        $race = Race::factory()->for(Championship::factory()->create(['bonus_enabled' => null]))->create(['bonus_enabled' => null]);
        $race->load('championship');

        $this->assertTrue($race->isBonusEnabled());
    }

    public function test_is_bonus_enabled_respects_race_setting(): void
    {
        $race = Race::factory()->create(['bonus_enabled' => false]);

        $this->assertFalse($race->isBonusEnabled());
    }

    public function test_is_bonus_enabled_falls_back_to_championship(): void
    {
        $championship = Championship::factory()->create(['bonus_enabled' => false]);
        $race = Race::factory()->for($championship)->create(['bonus_enabled' => null]);
        $race->load('championship');

        $this->assertFalse($race->isBonusEnabled());
    }
}

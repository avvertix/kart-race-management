<?php

namespace Tests\Feature;

use App\Models\Bonus;
use App\Models\BonusType;
use App\Models\Championship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Str;

class ChampionshipBonusControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_listing_bonuses_requires_login(): void
    {
        $championship = Championship::factory()->create();

        $response = $this->get(route('championships.bonuses.index', $championship));

        $response->assertRedirectToRoute('login');
    }

    public function test_creating_bonuses_requires_login(): void
    {
        $championship = Championship::factory()->create();

        $response = $this->get(route('championships.bonuses.create', $championship));

        $response->assertRedirectToRoute('login');
    }

    public function test_bonuses_can_be_listed(): void
    {
        $user = User::factory()->racemanager()->create();

        $championship = Championship::factory()
            ->has(Bonus::factory()->count(2))
            ->create();

        $response = $this
            ->actingAs($user)
            ->get(route('championships.bonuses.index', $championship));

        $response->assertSuccessful();

        $response->assertViewIs('bonus.index');

        $response->assertViewHas('bonuses', $championship->bonuses()->orderBy('driver', 'ASC')->get());
    }

    public function test_bonus_creation_requires_organizer_level(): void
    {
        $user = User::factory()->racemanager()->create();

        $championship = Championship::factory()
            ->create();

        $response = $this
            ->actingAs($user)
            ->get(route('championships.bonuses.create', $championship));

        $response->assertForbidden();
    }

    public function test_bonus_creation_form_shown(): void
    {
        $user = User::factory()->organizer()->create();

        $championship = Championship::factory()
            ->create();

        $response = $this
            ->actingAs($user)
            ->get(route('championships.bonuses.create', $championship));

        $response->assertSuccessful();

        $response->assertViewIs('bonus.create');

        $response->assertViewHas('championship', $championship);
    }
    
    public function test_bonus_created(): void
    {
        $user = User::factory()->organizer()->create();

        $championship = Championship::factory()
            ->create();

        $response = $this
            ->actingAs($user)
            ->from(route('championships.bonuses.create', $championship))
            ->post(route('championships.bonuses.store', $championship), [
                'driver' => 'Driver name',
                'driver_licence' => 'DRV-LC',
                'bonus_type' => BonusType::REGISTRATION_FEE->value,
                'amount' => 1,
            ]);

        $response->assertRedirectToRoute('championships.bonuses.index', $championship);

        $response->assertSessionHas('flash.banner', 'Bonus activated for Driver name.');

        $bonus = $championship->bonuses()->first();

        $this->assertInstanceOf(Bonus::class, $bonus);

        $this->assertEquals('Driver name', $bonus->driver);
        $this->assertEquals('DRV-LC', $bonus->driver_licence);
        $this->assertNull($bonus->contact_email);
        $this->assertEquals(1, $bonus->amount);
        $this->assertEquals(BonusType::REGISTRATION_FEE, $bonus->bonus_type);
    }
    
    public function test_bonus_not_created_when_name_above_maximum_length(): void
    {
        $user = User::factory()->organizer()->create();

        $championship = Championship::factory()
            ->create();

        $response = $this
            ->actingAs($user)
            ->from(route('championships.bonuses.create', $championship))
            ->post(route('championships.bonuses.store', $championship), [
                'driver' => 'Driver name ' . Str::random(250),
                'driver_licence' => 'DRV-LC',
                'bonus_type' => BonusType::REGISTRATION_FEE->value,
                'amount' => 1,
            ]);

        $response->assertRedirectToRoute('championships.bonuses.create', $championship);

        $response->assertSessionHasErrors('driver');

        $bonus = Bonus::first();

        $this->assertNull($bonus);
    }
    
    public function test_bonus_not_created_when_already_existing_with_same_licence(): void
    {
        $user = User::factory()->organizer()->create();

        $championship = Championship::factory()
            ->create();

        $bonus = Bonus::factory()
            ->recycle($championship)
            ->create();

        $response = $this
            ->actingAs($user)
            ->from(route('championships.bonuses.create', $championship))
            ->post(route('championships.bonuses.store', $championship), [
                'driver' => 'Driver name',
                'driver_licence' => $bonus->driver_licence,
                'bonus_type' => BonusType::REGISTRATION_FEE->value,
                'amount' => 1,
            ]);

        $response->assertRedirectToRoute('championships.bonuses.create', $championship);

        $response->assertSessionHasErrors('driver_licence');
    }


    public function test_bonus_edit_form_shown(): void
    {
        $user = User::factory()->organizer()->create();

        $bonus = Bonus::factory()
            ->create();

        $response = $this
            ->actingAs($user)
            ->get(route('bonuses.edit', $bonus));

        $response->assertSuccessful();

        $response->assertViewIs('bonus.edit');

        $response->assertViewHas('bonus', $bonus);

        $response->assertViewHas('championship', $bonus->championship);
    }

    public function test_bonus_updated(): void
    {
        $user = User::factory()->organizer()->create();

        $championship = Championship::factory()
            ->create();

        $bonus = Bonus::factory()
            ->recycle($championship)
            ->create();

        $response = $this
            ->actingAs($user)
            ->from(route('bonuses.edit', $bonus))
            ->put(route('bonuses.update', $bonus), [
                'driver' => $bonus->driver,
                'driver_licence' => $bonus->driver_licence,
                'bonus_type' => BonusType::REGISTRATION_FEE->value,
                'amount' => 3,
            ]);

        $response->assertRedirectToRoute('championships.bonuses.index', $bonus->championship);

        $response->assertSessionHas('flash.banner', "Bonus for {$bonus->driver} updated.");

        $updatedBonus = $bonus->fresh();

        $this->assertInstanceOf(Bonus::class, $updatedBonus);

        $this->assertEquals($bonus->driver, $updatedBonus->driver);
        $this->assertEquals($bonus->driver_licence, $updatedBonus->driver_licence);
        $this->assertEquals(3, $updatedBonus->amount);
        $this->assertEquals($bonus->bonus_type, $updatedBonus->bonus_type);
    }

    public function test_bonus_details_shown(): void
    {
        $user = User::factory()->organizer()->create();

        $bonus = Bonus::factory()
            ->create();

        $response = $this
            ->actingAs($user)
            ->get(route('bonuses.show', $bonus));

        $response->assertSuccessful();

        $response->assertViewIs('bonus.show');

        $response->assertViewHas('bonus', $bonus);

        $response->assertViewHas('championship', $bonus->championship);
    }
}

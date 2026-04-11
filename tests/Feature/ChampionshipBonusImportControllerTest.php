<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Bonus;
use App\Models\BonusType;
use App\Models\Championship;
use App\Models\User;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class ChampionshipBonusImportControllerTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_import_form_requires_login(): void
    {
        $championship = Championship::factory()->create();

        $response = $this->get(route('championships.bonuses.import.create', $championship));

        $response->assertRedirectToRoute('login');
    }

    public function test_import_store_requires_login(): void
    {
        $championship = Championship::factory()->create();

        $response = $this->post(route('championships.bonuses.import.store', $championship));

        $response->assertRedirectToRoute('login');
    }

    public function test_import_form_requires_organizer_level(): void
    {
        $user = User::factory()->racemanager()->create();
        $championship = Championship::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('championships.bonuses.import.create', $championship));

        $response->assertForbidden();
    }

    public function test_import_form_shown(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('championships.bonuses.import.create', $championship));

        $response->assertSuccessful();
        $response->assertViewIs('bonus.import');
        $response->assertViewHas('championship', $championship);
    }

    public function test_bonuses_imported_with_licence(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        $bonusType = BonusType::REGISTRATION_FEE->value;

        $response = $this
            ->actingAs($user)
            ->post(route('championships.bonuses.import.store', $championship), [
                'bonuses' => "Mario Rossi;DRV-0001;;{$bonusType};2\nLuigi Bianchi;DRV-0002;;{$bonusType};1",
            ]);

        $response->assertRedirectToRoute('championships.bonuses.index', $championship);
        $response->assertSessionHas('flash.banner', '2 bonuses imported.');

        $this->assertDatabaseCount('bonuses', 2);

        $bonus = $championship->bonuses()->where('driver', 'Mario Rossi')->first();
        $this->assertNotNull($bonus);
        $this->assertEquals('DRV-0001', $bonus->driver_licence);
        $this->assertNull($bonus->driver_fiscal_code);
        $this->assertEquals(2, $bonus->amount);
        $this->assertEquals(BonusType::REGISTRATION_FEE, $bonus->bonus_type);
    }

    public function test_bonuses_imported_with_fiscal_code(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        $bonusType = BonusType::REGISTRATION_FEE->value;

        $response = $this
            ->actingAs($user)
            ->post(route('championships.bonuses.import.store', $championship), [
                'bonuses' => "Mario Rossi;;RSSMR;{$bonusType};1",
            ]);

        $response->assertRedirectToRoute('championships.bonuses.index', $championship);

        $bonus = $championship->bonuses()->where('driver', 'Mario Rossi')->first();
        $this->assertNotNull($bonus);
        $this->assertNull($bonus->driver_licence);
        $this->assertEquals('RSSMR', $bonus->driver_fiscal_code);
        $this->assertEquals(hash('sha512', 'rssmr'), $bonus->driver_fiscal_code_hash);
    }

    public function test_bonuses_imported_with_both_licence_and_fiscal_code(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        $bonusType = BonusType::REGISTRATION_FEE->value;

        $response = $this
            ->actingAs($user)
            ->post(route('championships.bonuses.import.store', $championship), [
                'bonuses' => "Mario Rossi;DRV-0001;RSSMR;{$bonusType};1",
            ]);

        $response->assertRedirectToRoute('championships.bonuses.index', $championship);

        $bonus = $championship->bonuses()->where('driver', 'Mario Rossi')->first();
        $this->assertEquals('DRV-0001', $bonus->driver_licence);
        $this->assertEquals('RSSMR', $bonus->driver_fiscal_code);
    }

    public function test_import_fails_when_no_identifier_provided(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        $bonusType = BonusType::REGISTRATION_FEE->value;

        $response = $this
            ->actingAs($user)
            ->post(route('championships.bonuses.import.store', $championship), [
                'bonuses' => "Mario Rossi;;;{$bonusType};1",
            ]);

        $response->assertSessionHasErrors('bonuses');
        $this->assertDatabaseCount('bonuses', 0);
    }

    public function test_import_fails_when_driver_name_missing(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        $bonusType = BonusType::REGISTRATION_FEE->value;

        $response = $this
            ->actingAs($user)
            ->post(route('championships.bonuses.import.store', $championship), [
                'bonuses' => ";DRV-0001;;{$bonusType};1",
            ]);

        $response->assertSessionHasErrors('bonuses');
        $this->assertDatabaseCount('bonuses', 0);
    }

    public function test_import_fails_when_bonus_type_invalid(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('championships.bonuses.import.store', $championship), [
                'bonuses' => 'Mario Rossi;DRV-0001;;99;1',
            ]);

        $response->assertSessionHasErrors('bonuses');
        $this->assertDatabaseCount('bonuses', 0);
    }

    public function test_import_fails_when_amount_is_zero(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        $bonusType = BonusType::REGISTRATION_FEE->value;

        $response = $this
            ->actingAs($user)
            ->post(route('championships.bonuses.import.store', $championship), [
                'bonuses' => "Mario Rossi;DRV-0001;;{$bonusType};0",
            ]);

        $response->assertSessionHasErrors('bonuses');
        $this->assertDatabaseCount('bonuses', 0);
    }

    public function test_import_fails_when_licence_already_exists(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        Bonus::factory()->recycle($championship)->create([
            'driver_licence' => 'DRV-0001',
            'driver_licence_hash' => hash('sha512', 'DRV-0001'),
        ]);

        $bonusType = BonusType::REGISTRATION_FEE->value;

        $response = $this
            ->actingAs($user)
            ->post(route('championships.bonuses.import.store', $championship), [
                'bonuses' => "New Driver;DRV-0001;;{$bonusType};1",
            ]);

        $response->assertSessionHasErrors('bonuses');
        $this->assertDatabaseCount('bonuses', 1);
    }

    public function test_import_fails_when_fiscal_code_already_exists(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        Bonus::factory()->recycle($championship)->create([
            'driver_fiscal_code' => 'RSSMR',
            'driver_fiscal_code_hash' => hash('sha512', 'rssmr'),
        ]);

        $bonusType = BonusType::REGISTRATION_FEE->value;

        $response = $this
            ->actingAs($user)
            ->post(route('championships.bonuses.import.store', $championship), [
                'bonuses' => "New Driver;;RSSMR;{$bonusType};1",
            ]);

        $response->assertSessionHasErrors('bonuses');
        $this->assertDatabaseCount('bonuses', 1);
    }

    public function test_import_skips_blank_lines(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        $bonusType = BonusType::REGISTRATION_FEE->value;

        $response = $this
            ->actingAs($user)
            ->post(route('championships.bonuses.import.store', $championship), [
                'bonuses' => "Mario Rossi;DRV-0001;;{$bonusType};1\n\nLuigi Bianchi;DRV-0002;;{$bonusType};1\n",
            ]);

        $response->assertRedirectToRoute('championships.bonuses.index', $championship);
        $this->assertDatabaseCount('bonuses', 2);
    }

    public function test_import_does_not_persist_any_row_when_one_line_is_invalid(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        $bonusType = BonusType::REGISTRATION_FEE->value;

        $response = $this
            ->actingAs($user)
            ->post(route('championships.bonuses.import.store', $championship), [
                'bonuses' => "Mario Rossi;DRV-0001;;{$bonusType};1\n;DRV-0002;;{$bonusType};1",
            ]);

        $response->assertSessionHasErrors('bonuses');
        $this->assertDatabaseCount('bonuses', 0);
    }
}

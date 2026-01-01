<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\ChampionshipTire;
use App\Models\User;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class CopyChampionshipTiresControllerTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_copy_form_requires_login(): void
    {
        $championship = Championship::factory()->create();

        $response = $this->get(route('championships.tire-options.copy', $championship));

        $response->assertRedirectToRoute('login');
    }

    public function test_copy_form_requires_organizer_level(): void
    {
        $user = User::factory()->racemanager()->create();
        $championship = Championship::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('championships.tire-options.copy', $championship));

        $response->assertForbidden();
    }

    public function test_copy_form_shown_successfully(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        // Create other championships with tires
        $sourceChampionship1 = Championship::factory()
            ->has(ChampionshipTire::factory()->count(2), 'tires')
            ->create(['title' => 'Source Championship 1']);

        $sourceChampionship2 = Championship::factory()
            ->has(ChampionshipTire::factory()->count(3), 'tires')
            ->create(['title' => 'Source Championship 2']);

        // Championship without tires should not appear
        Championship::factory()->create(['title' => 'Empty Championship']);

        $response = $this
            ->actingAs($user)
            ->get(route('championships.tire-options.copy', $championship));

        $response->assertSuccessful();
        $response->assertViewIs('championship-tire.copy');
        $response->assertViewHas('championship', $championship);

        $sourceChampionships = $response->viewData('sourceChampionships');
        $this->assertCount(2, $sourceChampionships);
        $this->assertTrue($sourceChampionships->contains($sourceChampionship1));
        $this->assertTrue($sourceChampionships->contains($sourceChampionship2));
    }

    public function test_copy_form_excludes_current_championship(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()
            ->has(ChampionshipTire::factory()->count(2), 'tires')
            ->create();

        $response = $this
            ->actingAs($user)
            ->get(route('championships.tire-options.copy', $championship));

        $response->assertSuccessful();

        $sourceChampionships = $response->viewData('sourceChampionships');
        $this->assertFalse($sourceChampionships->contains($championship));
    }

    public function test_copy_form_only_shows_championships_with_tires(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        // Create championships without tires
        Championship::factory()->count(3)->create();

        $response = $this
            ->actingAs($user)
            ->get(route('championships.tire-options.copy', $championship));

        $response->assertSuccessful();

        $sourceChampionships = $response->viewData('sourceChampionships');
        $this->assertCount(0, $sourceChampionships);
    }

    public function test_store_copy_requires_login(): void
    {
        $championship = Championship::factory()->create();
        $sourceChampionship = Championship::factory()
            ->has(ChampionshipTire::factory()->count(2), 'tires')
            ->create();

        $response = $this->post(route('championships.tire-options.store-copy', $championship), [
            'source_championship' => $sourceChampionship->id,
        ]);

        $response->assertRedirectToRoute('login');
    }

    public function test_store_copy_requires_organizer_level(): void
    {
        $user = User::factory()->racemanager()->create();
        $championship = Championship::factory()->create();
        $sourceChampionship = Championship::factory()
            ->has(ChampionshipTire::factory()->count(2), 'tires')
            ->create();

        $response = $this
            ->actingAs($user)
            ->post(route('championships.tire-options.store-copy', $championship), [
                'source_championship' => $sourceChampionship->id,
            ]);

        $response->assertForbidden();
    }

    public function test_store_copy_validates_required_source_championship(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('championships.tire-options.store-copy', $championship), [
                'source_championship' => '',
            ]);

        $response->assertSessionHasErrors('source_championship');
    }

    public function test_store_copy_validates_source_championship_must_be_integer(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('championships.tire-options.store-copy', $championship), [
                'source_championship' => 'not-an-integer',
            ]);

        $response->assertSessionHasErrors('source_championship');
    }

    public function test_store_copy_validates_source_championship_must_exist(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('championships.tire-options.store-copy', $championship), [
                'source_championship' => 99999,
            ]);

        $response->assertSessionHasErrors('source_championship');
    }

    public function test_store_copy_successfully_copies_tires(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        // Create source championship with tires
        $sourceChampionship = Championship::factory()->create();
        $sourceChampionship->tires()->create([
            'name' => 'Bridgestone YDS',
            'code' => 'BG-YDS',
            'price' => 15000,
        ]);
        $sourceChampionship->tires()->create([
            'name' => 'Vega XH3',
            'code' => 'VG-XH3',
            'price' => 12000,
        ]);

        $this->assertCount(0, $championship->tires);

        $response = $this
            ->actingAs($user)
            ->post(route('championships.tire-options.store-copy', $championship), [
                'source_championship' => $sourceChampionship->id,
            ]);

        $response->assertRedirect(route('championships.tire-options.index', $championship));
        $response->assertSessionHas('flash.banner');

        $championship->refresh();
        $this->assertCount(2, $championship->tires);

        $copiedTire = $championship->tires->firstWhere('code', 'BG-YDS');
        $this->assertEquals('Bridgestone YDS', $copiedTire->name);
        $this->assertEquals(15000, $copiedTire->price);
    }

    public function test_store_copy_creates_new_ulids_for_tires(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        $sourceChampionship = Championship::factory()
            ->has(ChampionshipTire::factory()->count(2), 'tires')
            ->create();

        $sourceUlids = $sourceChampionship->tires->pluck('ulid')->toArray();

        $response = $this
            ->actingAs($user)
            ->post(route('championships.tire-options.store-copy', $championship), [
                'source_championship' => $sourceChampionship->id,
            ]);

        $response->assertRedirect(route('championships.tire-options.index', $championship));

        $championship->refresh();
        $copiedUlids = $championship->tires->pluck('ulid')->toArray();

        // ULIDs should be different
        $this->assertNotEquals($sourceUlids, $copiedUlids);
        $this->assertCount(2, array_unique($copiedUlids));
    }

    public function test_store_copy_preserves_source_championship_data(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        $sourceChampionship = Championship::factory()->create();
        $originalTiresCount = $sourceChampionship->tires()->count();

        $sourceChampionship->tires()->create([
            'name' => 'Test Tire',
            'code' => 'TEST',
            'price' => 10000,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('championships.tire-options.store-copy', $championship), [
                'source_championship' => $sourceChampionship->id,
            ]);

        $response->assertRedirect(route('championships.tire-options.index', $championship));

        $sourceChampionship->refresh();

        // Source should still have the same tires
        $this->assertCount($originalTiresCount + 1, $sourceChampionship->tires);
    }

    public function test_store_copy_displays_success_message_with_count(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        $sourceChampionship = Championship::factory()
            ->has(ChampionshipTire::factory()->count(5), 'tires')
            ->create();

        $response = $this
            ->actingAs($user)
            ->post(route('championships.tire-options.store-copy', $championship), [
                'source_championship' => $sourceChampionship->id,
            ]);

        $response->assertRedirect(route('championships.tire-options.index', $championship));
        $response->assertSessionHas('flash.banner');

        $banner = session('flash.banner');
        $this->assertStringContainsString('5', $banner);
    }

    public function test_store_copy_handles_empty_source_championship(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        // Create source championship but then remove all tires
        $sourceChampionship = Championship::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('championships.tire-options.store-copy', $championship), [
                'source_championship' => $sourceChampionship->id,
            ]);

        $response->assertRedirect(route('championships.tire-options.index', $championship));

        $championship->refresh();
        $this->assertCount(0, $championship->tires);
    }

    public function test_store_copy_to_championship_with_existing_tires(): void
    {
        $user = User::factory()->organizer()->create();

        // Target championship already has tires
        $championship = Championship::factory()->create();
        $existingTire = $championship->tires()->create([
            'name' => 'Existing Tire',
            'code' => 'EXIST',
            'price' => 20000,
        ]);

        // Source championship has different tires
        $sourceChampionship = Championship::factory()->create();
        $sourceChampionship->tires()->create([
            'name' => 'New Tire',
            'code' => 'NEW',
            'price' => 15000,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('championships.tire-options.store-copy', $championship), [
                'source_championship' => $sourceChampionship->id,
            ]);

        $response->assertRedirect(route('championships.tire-options.index', $championship));

        $championship->refresh();

        // Should have both existing and copied tires
        $this->assertCount(2, $championship->tires);
        $this->assertNotNull($championship->tires->firstWhere('code', 'EXIST'));
        $this->assertNotNull($championship->tires->firstWhere('code', 'NEW'));
    }

    public function test_store_copy_copies_all_tire_properties(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        $sourceChampionship = Championship::factory()->create();
        $sourceChampionship->tires()->create([
            'name' => 'MG SM',
            'code' => 'MG-SM',
            'price' => 18000,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('championships.tire-options.store-copy', $championship), [
                'source_championship' => $sourceChampionship->id,
            ]);

        $response->assertRedirect(route('championships.tire-options.index', $championship));

        $championship->refresh();
        $copiedTire = $championship->tires->first();

        $this->assertEquals('MG SM', $copiedTire->name);
        $this->assertEquals('MG-SM', $copiedTire->code);
        $this->assertEquals(18000, $copiedTire->price);
        $this->assertEquals($championship->id, $copiedTire->championship_id);
    }
}

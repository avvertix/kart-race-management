<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\ChampionshipTire;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ChampionshipTiresControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_listing_tires_requires_login(): void
    {
        $championship = Championship::factory()->create();

        $response = $this->get(route('championships.tire-options.index', $championship));

        $response->assertRedirectToRoute('login');
    }

    public function test_creating_tires_requires_login(): void
    {
        $championship = Championship::factory()->create();

        $response = $this->get(route('championships.tire-options.create', $championship));

        $response->assertRedirectToRoute('login');
    }

    public function test_tires_can_be_listed(): void
    {
        $user = User::factory()->racemanager()->create();

        $championship = Championship::factory()
            ->has(ChampionshipTire::factory()->count(2), 'tires')
            ->create();

        $response = $this
            ->actingAs($user)
            ->get(route('championships.tire-options.index', $championship));

        $response->assertSuccessful();

        $response->assertViewIs('championship-tire.index');

        $response->assertViewHas('tires', $championship->tires()->orderBy('name', 'ASC')->get());
    }

    public function test_tire_creation_requires_organizer_level(): void
    {
        $user = User::factory()->racemanager()->create();

        $championship = Championship::factory()
            ->create();

        $response = $this
            ->actingAs($user)
            ->get(route('championships.tire-options.create', $championship));

        $response->assertForbidden();
    }

    public function test_tire_creation_form_shown(): void
    {
        $user = User::factory()->organizer()->create();

        $championship = Championship::factory()
            ->create();

        $response = $this
            ->actingAs($user)
            ->get(route('championships.tire-options.create', $championship));

        $response->assertSuccessful();

        $response->assertViewIs('championship-tire.create');

        $response->assertViewHas('championship', $championship);
    }
    
    public function test_tire_created(): void
    {
        $user = User::factory()->organizer()->create();

        $championship = Championship::factory()
            ->create();

        $response = $this
            ->actingAs($user)
            ->from(route('championships.tire-options.create', $championship))
            ->post(route('championships.tire-options.store', $championship), [
                'name' => 'Tire name',
                'price' => 12000
            ]);

        $response->assertRedirectToRoute('championships.tire-options.index', $championship);

        $response->assertSessionHas('flash.banner', 'Tire name created.');

        $tire = ChampionshipTire::first();

        $this->assertInstanceOf(ChampionshipTire::class, $tire);

        $this->assertEquals('Tire name', $tire->name);
        $this->assertNull($tire->code);
        $this->assertEquals(12000, $tire->price);
    }
    
    public function test_tire_created_with_same_name_as_tire_in_another_championship(): void
    {
        $user = User::factory()->organizer()->create();

        $championship = Championship::factory()
            ->create();

        $otherTire = ChampionshipTire::factory()
            ->create([
                'name' => 'Tire name',
            ]);

        $response = $this
            ->actingAs($user)
            ->from(route('championships.tire-options.create', $championship))
            ->post(route('championships.tire-options.store', $championship), [
                'name' => 'Tire name',
                'price' => 12000
            ]);

        $response->assertRedirectToRoute('championships.tire-options.index', $championship);

        $response->assertSessionHas('flash.banner', 'Tire name created.');

        $tire = $championship->tires()->first();

        $this->assertInstanceOf(ChampionshipTire::class, $tire);

        $this->assertEquals('Tire name', $tire->name);
        $this->assertNull($tire->code);
        $this->assertEquals(12000, $tire->price);
    }


    public function test_tire_edit_form_shown(): void
    {
        $user = User::factory()->organizer()->create();

        $tire = ChampionshipTire::factory()
            ->create();

        $response = $this
            ->actingAs($user)
            ->get(route('tire-options.edit', $tire));

        $response->assertSuccessful();

        $response->assertViewIs('championship-tire.edit');

        $response->assertViewHas('tire', $tire);

        $response->assertViewHas('championship', $tire->championship);
    }

    public function test_tire_updated(): void
    {
        $user = User::factory()->organizer()->create();

        $tire = ChampionshipTire::factory()
            ->create();

        $response = $this
            ->actingAs($user)
            ->from(route('tire-options.edit', $tire))
            ->put(route('tire-options.update', $tire), [
                'name' => 'Tire name',
                'price' => 40000,
            ]);

        $response->assertRedirectToRoute('championships.tire-options.index', $tire->championship);

        $response->assertSessionHas('flash.banner', 'Tire name updated.');

        $updatedTire = $tire->fresh();

        $lastActivity = $updatedTire->activities()->get()->last();

        $this->assertInstanceOf(ChampionshipTire::class, $updatedTire);

        $this->assertEquals('Tire name', $updatedTire->name);
        $this->assertNull($updatedTire->code);
        $this->assertEquals(40000, $updatedTire->price);
        $this->assertEquals(['price' => 40000], $lastActivity->changes()->get('attributes'));
        $this->assertEquals(['price' => $tire->price], $lastActivity->changes()->get('old'));
    }

    public function test_tire_price_updated_when_same_tire_is_present_in_another_championship(): void
    {
        $user = User::factory()->organizer()->create();

        $otherTire = ChampionshipTire::factory()
            ->create([
                'name' => 'MG',
            ]);

        $tire = ChampionshipTire::factory()
            ->create([
                'name' => 'MG',
            ]);

        $response = $this
            ->actingAs($user)
            ->from(route('tire-options.edit', $tire))
            ->put(route('tire-options.update', $tire), [
                'name' => $tire->name,
                'price' => 40000,
            ]);

        $response->assertRedirectToRoute('championships.tire-options.index', $tire->championship);

        $response->assertSessionHas('flash.banner', "{$tire->name} updated.");

        $updatedTire = $tire->fresh();

        $lastActivity = $updatedTire->activities()->get()->last();

        $this->assertInstanceOf(ChampionshipTire::class, $updatedTire);

        $this->assertEquals($tire->name, $updatedTire->name);
        $this->assertNull($updatedTire->code);
        $this->assertEquals(40000, $updatedTire->price);
        $this->assertEquals(['price' => 40000], $lastActivity->changes()->get('attributes'));
        $this->assertEquals(['price' => $tire->price], $lastActivity->changes()->get('old'));
    }

    public function test_tire_details_shown(): void
    {
        $user = User::factory()->organizer()->create();

        $tire = ChampionshipTire::factory()
            ->create();

        $response = $this
            ->actingAs($user)
            ->get(route('tire-options.show', $tire));

        $response->assertSuccessful();

        $response->assertViewIs('championship-tire.show');

        $response->assertViewHas('tire', $tire);

        $response->assertViewHas('championship', $tire->championship);
    }
}

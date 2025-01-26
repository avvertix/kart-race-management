<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\Race;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ChampionshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_championships_list_can_be_rendered()
    {
        $user = User::factory()->organizer()->create();

        $expectedChamp = Championship::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('championships.index'));

        $response->assertOk();

        $championships = $response->viewData('championships');

        $this->assertInstanceOf(LengthAwarePaginator::class, $championships);
        $this->assertTrue($championships->first()->is($expectedChamp));
    }

    public function test_new_championship_can_be_created()
    {
        $user = User::factory()->organizer()->create();

        $this->travelTo(Carbon::parse('2023-03-05'));

        $response = $this
            ->actingAs($user)
            ->from(route('championships.create'))
            ->post(route('championships.store'), [
                'start' => '2023-03-12',
                'end' => '2023-12-02',
                'title' => 'Kartsport 2023',
                'description' => 'a little description',
            ]);

        $this->travelBack();

        $response->assertRedirectToRoute('championships.index');

        $response->assertSessionHasNoErrors();

        $response->assertSessionHas('flash.banner', 'Kartsport 2023 created.');

        $champ = Championship::first();

        $this->assertInstanceOf(Championship::class, $champ);
        $this->assertEquals('Kartsport 2023', $champ->title);
        $this->assertEquals('a little description', $champ->description);
        $this->assertEquals(Carbon::parse('2023-03-12'), $champ->start_at);
        $this->assertEquals(Carbon::parse('2023-12-02'), $champ->end_at);
    }

    public function test_new_championship_title_can_be_generated()
    {
        $user = User::factory()->organizer()->create();

        $this->travelTo(Carbon::parse('2023-03-05'));

        $response = $this
            ->actingAs($user)
            ->from(route('championships.create'))
            ->post(route('championships.store'), [
                'start' => '2023-03-12',
                'end' => '2023-12-02',
                'title' => '',
                'description' => 'a little description',
            ]);

        $this->travelBack();

        $response->assertRedirectToRoute('championships.index');

        $response->assertSessionHasNoErrors();

        $response->assertSessionHas('flash.banner', '2023 Championship created.');

        $champ = Championship::first();

        $this->assertInstanceOf(Championship::class, $champ);
        $this->assertEquals('2023 Championship', $champ->title);
        $this->assertEquals('a little description', $champ->description);
        $this->assertEquals(Carbon::parse('2023-03-12'), $champ->start_at);
        $this->assertEquals(Carbon::parse('2023-12-02'), $champ->end_at);
    }

    public function test_creating_championship_requires_authentication()
    {
        $response = $this
            ->from(route('championships.create'))
            ->post(route('championships.store'), [
                'start' => '2023-03-12',
                'end' => '2023-12-02',
                'title' => '',
                'description' => 'a little description',
            ]);

        $response->assertRedirect('/login');
    }

    public function test_creating_championship_requires_permission()
    {
        $user = User::factory()->timekeeper()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('championships.create'))
            ->post(route('championships.store'), [
                'start' => '2023-03-12',
                'end' => '2023-12-02',
                'title' => '',
                'description' => 'a little description',
            ]);

        $response->assertForbidden();
    }

    public function test_championship_rendered()
    {
        $user = User::factory()->organizer()->create();

        $race = Race::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('championships.show', ['championship' => $race->championship]));

        $response->assertOk();

        $races = $response->viewData('races');

        $this->assertInstanceOf(Collection::class, $races);
        $this->assertTrue($races->first()->is($race));
        $this->assertTrue($response->viewData('championship')->is($race->championship));

    }

    public function test_championship_can_be_updated()
    {
        $user = User::factory()->organizer()->create();

        $existing = Championship::factory()->create();

        $this->travelTo(Carbon::parse('2023-03-05'));

        $response = $this
            ->actingAs($user)
            ->from(route('championships.show', $existing))
            ->put(route('championships.update', $existing), [
                'start' => '2023-03-12',
                'end' => '2023-12-02',
                'title' => 'Kartsport changed 2023',
                'description' => 'a little description',
            ]);

        $this->travelBack();

        $response->assertRedirectToRoute('championships.show', $existing);

        $response->assertSessionHasNoErrors();

        $response->assertSessionHas('flash.banner', 'Kartsport changed 2023 updated.');

        $champ = Championship::first();

        $this->assertInstanceOf(Championship::class, $champ);
        $this->assertEquals('Kartsport changed 2023', $champ->title);
        $this->assertEquals('a little description', $champ->description);
        $this->assertEquals(Carbon::parse('2023-03-12'), $champ->start_at);
        $this->assertEquals(Carbon::parse('2023-12-02'), $champ->end_at);
    }

    public function test_championship_cannot_be_updated_when_races_are_present()
    {
        $user = User::factory()->organizer()->create();

        $existing = Championship::factory()
            ->has(Race::factory()->count(1))
            ->create();

        $response = $this
            ->actingAs($user)
            ->from(route('championships.edit', $existing))
            ->put(route('championships.update', $existing), [
                'start' => '2023-03-12',
                'end' => '2023-12-02',
                'title' => 'Kartsport 2023',
                'description' => 'a little description',
            ]);

        $response->assertRedirectToRoute('championships.edit', $existing);

        $response->assertSessionHasErrors('races', __('Championship contains races. Update is allowed only when no races are present.'));

        $champ = Championship::first();

        $this->assertInstanceOf(Championship::class, $champ);
        $this->assertEquals($existing->title, $champ->title);
        $this->assertEquals($existing->description, $champ->description);
        $this->assertEquals($existing->start_at, $champ->start_at);
        $this->assertEquals($existing->end_at, $champ->end_at);
    }
}

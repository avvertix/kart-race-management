<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\Race;
use App\Models\RaceType;
use App\Models\User;
use Carbon\Carbon;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class RaceTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_new_race_can_be_created()
    {
        $user = User::factory()->organizer()->create();

        $championship = Championship::factory()->create();

        $this->travelTo(Carbon::parse('2023-03-04'));

        $response = $this
            ->actingAs($user)
            ->from(route('championships.races.create', $championship))
            ->post(route('championships.races.store', $championship), [
                'start' => '2023-03-05',
                'end' => '2023-03-05',
                'track' => 'Franciacorta',
                'title' => 'First Race',
                'description' => 'a little description',
            ]);

        $this->travelBack();

        $response->assertRedirectToRoute('championships.show', ['championship' => $championship]);

        $response->assertSessionHasNoErrors();

        $response->assertSessionHas('flash.banner', 'First Race created.');

        $race = Race::first();

        $this->assertInstanceOf(Race::class, $race);
        $this->assertEquals('First Race', $race->title);
        $this->assertEquals('a little description', $race->description);
        $this->assertEquals('Franciacorta', $race->track);
        $this->assertTrue($race->championship->is($championship));
        $this->assertEquals(RaceType::LOCAL, $race->type);
        $this->assertEquals(Carbon::parse('2023-03-05 09:00:00', config('races.timezone'))->setTimezone(config('app.timezone'))->toDateTimeString(), $race->event_start_at->toDateTimeString());
        $this->assertEquals(Carbon::parse('2023-03-05 18:00:00', config('races.timezone'))->setTimezone(config('app.timezone'))->toDateTimeString(), $race->event_end_at->toDateTimeString());
        $this->assertEquals(Carbon::parse('2023-02-26 09:00:00', config('races.timezone'))->setTimezone(config('app.timezone'))->toDateTimeString(), $race->registration_opens_at->toDateTimeString());
        $this->assertEquals(Carbon::parse('2023-03-05 08:00:00', config('races.timezone'))->setTimezone(config('app.timezone'))->toDateTimeString(), $race->registration_closes_at->toDateTimeString());
    }

    public function test_new_race_can_be_created_with_custom_registration_period()
    {
        $user = User::factory()->organizer()->create();

        $championship = Championship::factory()->create();

        $this->travelTo(Carbon::parse('2023-03-04'));

        $response = $this
            ->actingAs($user)
            ->from(route('championships.races.create', $championship))
            ->post(route('championships.races.store', $championship), [
                'start' => '2023-03-05',
                'end' => '2023-03-05',
                'track' => 'Franciacorta',
                'title' => 'First Race',
                'description' => 'a little description',
                'registration_opens_at' => '2023-02-20 08:00:00',
                'registration_closes_at' => '2023-03-04 19:00:00',
            ]);

        $this->travelBack();

        $response->assertRedirectToRoute('championships.show', ['championship' => $championship]);

        $response->assertSessionHasNoErrors();

        $response->assertSessionHas('flash.banner', 'First Race created.');

        $race = Race::first();

        $this->assertInstanceOf(Race::class, $race);
        $this->assertEquals('First Race', $race->title);
        $this->assertEquals('a little description', $race->description);
        $this->assertEquals('Franciacorta', $race->track);
        $this->assertTrue($race->championship->is($championship));
        $this->assertEquals(RaceType::LOCAL, $race->type);
        $this->assertEquals(Carbon::parse('2023-03-05 09:00:00', config('races.timezone'))->setTimezone(config('app.timezone'))->toDateTimeString(), $race->event_start_at->toDateTimeString());
        $this->assertEquals(Carbon::parse('2023-03-05 18:00:00', config('races.timezone'))->setTimezone(config('app.timezone'))->toDateTimeString(), $race->event_end_at->toDateTimeString());
        $this->assertEquals(Carbon::parse('2023-02-20 08:00:00', config('races.timezone'))->setTimezone(config('app.timezone'))->toDateTimeString(), $race->registration_opens_at->toDateTimeString());
        $this->assertEquals(Carbon::parse('2023-03-04 19:00:00', config('races.timezone'))->setTimezone(config('app.timezone'))->toDateTimeString(), $race->registration_closes_at->toDateTimeString());
    }

    public function test_new_race_with_participant_limit_can_be_created()
    {
        $user = User::factory()->organizer()->create();

        $championship = Championship::factory()->create();

        $this->travelTo(Carbon::parse('2023-03-04'));

        $response = $this
            ->actingAs($user)
            ->from(route('championships.races.create', $championship))
            ->post(route('championships.races.store', $championship), [
                'start' => '2023-03-05',
                'end' => '2023-03-05',
                'track' => 'Franciacorta',
                'title' => 'First Race',
                'description' => 'a little description',
                'participants_total_limit' => 10,
            ]);

        $this->travelBack();

        $response->assertSessionHasNoErrors();

        $race = Race::first();

        $this->assertInstanceOf(Race::class, $race);
        $this->assertTrue($race->hasTotalParticipantLimit());
        $this->assertEquals(10, $race->getTotalParticipantLimit());
    }

    public function test_new_race_with_specific_type_can_be_created()
    {
        $user = User::factory()->organizer()->create();

        $championship = Championship::factory()->create();

        $this->travelTo(Carbon::parse('2023-03-04'));

        $response = $this
            ->actingAs($user)
            ->from(route('championships.races.create', $championship))
            ->post(route('championships.races.store', $championship), [
                'start' => '2023-03-05',
                'end' => '2023-03-05',
                'track' => 'Franciacorta',
                'title' => 'First Race',
                'description' => 'a little description',
                'race_type' => RaceType::ZONE->value,
            ]);

        $this->travelBack();

        $response->assertSessionHasNoErrors();

        $race = Race::first();

        $this->assertInstanceOf(Race::class, $race);
        $this->assertEquals(RaceType::ZONE, $race->type);
    }

    public function test_race_can_be_updated()
    {
        $user = User::factory()->organizer()->create();

        $existingRace = Race::factory()->create();

        $this->travelTo(Carbon::parse('2023-03-04'));

        $response = $this
            ->actingAs($user)
            ->from(route('races.edit', $existingRace))
            ->put(route('races.update', $existingRace), [
                'start' => '2023-03-05',
                'end' => '2023-03-05',
                'track' => 'Franciacorta',
                'title' => 'First Updated Race',
                'description' => 'a little description',
                'participants_total_limit' => 10,
                'race_type' => RaceType::INTERNATIONAL->value,
            ]);

        $this->travelBack();

        $response->assertRedirectToRoute('races.show', $existingRace);

        $response->assertSessionHasNoErrors();

        $response->assertSessionHas('flash.banner', 'First Updated Race saved.');

        $race = Race::first();

        $this->assertInstanceOf(Race::class, $race);
        $this->assertEquals('First Updated Race', $race->title);
        $this->assertEquals('a little description', $race->description);
        $this->assertEquals('Franciacorta', $race->track);
        $this->assertTrue($race->championship->is($existingRace->championship));
        $this->assertEquals(Carbon::parse('2023-03-05 09:00:00', config('races.timezone'))->setTimezone(config('app.timezone'))->toDateTimeString(), $race->event_start_at->toDateTimeString());
        $this->assertEquals(Carbon::parse('2023-03-05 18:00:00', config('races.timezone'))->setTimezone(config('app.timezone'))->toDateTimeString(), $race->event_end_at->toDateTimeString());
        $this->assertEquals(Carbon::parse('2023-02-26 09:00:00', config('races.timezone'))->setTimezone(config('app.timezone'))->toDateTimeString(), $race->registration_opens_at->toDateTimeString());
        $this->assertEquals(Carbon::parse('2023-03-05 08:00:00', config('races.timezone'))->setTimezone(config('app.timezone'))->toDateTimeString(), $race->registration_closes_at->toDateTimeString());
        $this->assertTrue($race->hasTotalParticipantLimit());
        $this->assertEquals(10, $race->getTotalParticipantLimit());
        $this->assertEquals(RaceType::INTERNATIONAL, $race->type);
    }

    public function test_race_can_be_updated_with_custom_registration_openings()
    {
        $user = User::factory()->organizer()->create();

        $existingRace = Race::factory()->create();

        $this->travelTo(Carbon::parse('2023-03-04'));

        $response = $this
            ->actingAs($user)
            ->from(route('races.edit', $existingRace))
            ->put(route('races.update', $existingRace), [
                'start' => '2023-03-05',
                'end' => '2023-03-05',
                'track' => 'Franciacorta',
                'title' => 'First Updated Race',
                'description' => 'a little description',
                'participants_total_limit' => 10,
                'race_type' => RaceType::INTERNATIONAL->value,
                'registration_opens_at' => '2023-02-20 08:00:00',
                'registration_closes_at' => '2023-03-04 19:00:00',
            ]);

        $this->travelBack();

        $response->assertRedirectToRoute('races.show', $existingRace);

        $response->assertSessionHasNoErrors();

        $response->assertSessionHas('flash.banner', 'First Updated Race saved.');

        $race = Race::first();

        $this->assertInstanceOf(Race::class, $race);
        $this->assertEquals('First Updated Race', $race->title);
        $this->assertEquals('a little description', $race->description);
        $this->assertEquals('Franciacorta', $race->track);
        $this->assertTrue($race->championship->is($existingRace->championship));
        $this->assertEquals(Carbon::parse('2023-03-05 09:00:00', config('races.timezone'))->setTimezone(config('app.timezone'))->toDateTimeString(), $race->event_start_at->toDateTimeString());
        $this->assertEquals(Carbon::parse('2023-03-05 18:00:00', config('races.timezone'))->setTimezone(config('app.timezone'))->toDateTimeString(), $race->event_end_at->toDateTimeString());
        $this->assertEquals(Carbon::parse('2023-02-20 08:00:00', config('races.timezone'))->setTimezone(config('app.timezone'))->toDateTimeString(), $race->registration_opens_at->toDateTimeString());
        $this->assertEquals(Carbon::parse('2023-03-04 19:00:00', config('races.timezone'))->setTimezone(config('app.timezone'))->toDateTimeString(), $race->registration_closes_at->toDateTimeString());
        $this->assertTrue($race->hasTotalParticipantLimit());
        $this->assertEquals(10, $race->getTotalParticipantLimit());
        $this->assertEquals(RaceType::INTERNATIONAL, $race->type);
    }
}

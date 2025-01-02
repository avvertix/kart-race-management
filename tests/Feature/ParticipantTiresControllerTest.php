<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\Race;
use App\Models\Tire;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParticipantTiresControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_tire_creation_page_requires_authentication()
    {
        $participant = Participant::factory()->create();

        $response = $this
            ->get(route('participants.tires.create', $participant));

        $response->assertRedirectToRoute('login');

    }

    public function test_tire_creation_page_not_accessible_by_timekeeper()
    {
        $user = User::factory()->timekeeper()->create();

        $participant = Participant::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('participants.tires.create', $participant));

        $response->assertForbidden();

    }

    public function test_tire_creation_page_loads()
    {
        $user = User::factory()->tireagent()->create();

        $participant = Participant::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('participants.tires.create', $participant));

        $response->assertSuccessful();

        $response->assertViewHas('participant', $participant);

        $response->assertViewHas('race', $participant->race);

        $response->assertViewHas('tireLimit', 4);

    }

    public function test_tire_can_be_assigned()
    {
        $user = User::factory()->tireagent()->create();

        $participant = Participant::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('participants.tires.create', $participant))
            ->post(route('participants.tires.store', $participant), [
                'tires' => ['T1', 'T2', 'T3', 'T4'],
            ]);

        $response->assertSessionHasNoErrors();

        $response->assertRedirectToRoute('participants.tires.index', $participant);

        $tires = $participant->fresh()->tires()->get(['code'])->pluck('code')->values();

        $this->assertEquals(4, $tires->count());
        $this->assertEquals(['T1', 'T2', 'T3', 'T4'], $tires->toArray());
    }

    public function test_tire_not_assigned_if_driver_already_has_five()
    {
        $user = User::factory()->tireagent()->create();

        $race = Race::factory()->create();

        $participant = Participant::factory()
            ->has(Tire::factory()->count(5)->state([
                'race_id' => $race->getKey(),
            ]))
            ->create([
                'race_id' => $race->getKey(),
                'championship_id' => $race->championship->getKey(),
            ]);

        $response = $this
            ->actingAs($user)
            ->from(route('participants.tires.create', $participant))
            ->post(route('participants.tires.store', $participant), [
                'tires' => ['T5'],
            ]);

        $response->assertSessionHasErrors([
            'tires' => 'Participant already have 5 tires assigned',
        ]);

        $response->assertRedirectToRoute('participants.tires.create', $participant);

        $this->assertEquals(5, $participant->fresh()->tires()->count());
    }

    public function test_tire_not_assigned_if_already_attached_to_another_participant()
    {
        $user = User::factory()->tireagent()->create();

        $race = Race::factory()->create();

        $participant = Participant::factory()
            ->has(Tire::factory()->count(2)->state([
                'race_id' => $race->getKey(),
            ]))
            ->create([
                'race_id' => $race->getKey(),
                'championship_id' => $race->championship->getKey(),
            ]);

        $response = $this
            ->actingAs($user)
            ->from(route('participants.tires.create', $participant))
            ->post(route('participants.tires.store', $participant), [
                'tires' => [$participant->tires()->first()->code],
            ]);

        $response->assertSessionHasErrors('tires.0');

        $response->assertRedirectToRoute('participants.tires.create', $participant);

        $this->assertEquals(2, $participant->fresh()->tires()->count());
    }

    public function test_tire_edit_page_loads()
    {
        $user = User::factory()->tireagent()->create();

        $race = Race::factory()->create();

        $participant = Participant::factory()
            ->has(Tire::factory()->count(2)->state([
                'race_id' => $race->getKey(),
            ]))
            ->create([
                'race_id' => $race->getKey(),
                'championship_id' => $race->championship->getKey(),
            ]);

        $tire = $participant->tires()->first();

        $response = $this
            ->actingAs($user)
            ->get(route('tires.edit', $tire));

        $response->assertSuccessful();

        $response->assertViewHas('participant', $participant);

        $response->assertViewHas('race', $participant->race);

        $response->assertViewHas('tire', $tire);

    }

    public function test_tire_can_be_updated()
    {
        $user = User::factory()->tireagent()->create();

        $race = Race::factory()->create();

        $participant = Participant::factory()
            ->has(Tire::factory()->count(2)->state([
                'race_id' => $race->getKey(),
            ]))
            ->create([
                'race_id' => $race->getKey(),
                'championship_id' => $race->championship->getKey(),
            ]);

        $tire = $participant->tires()->first();

        $response = $this
            ->actingAs($user)
            ->from(route('tires.edit', $tire))
            ->put(route('tires.update', $tire), [
                'tire' => 'TNEW',
            ]);

        $response->assertRedirectToRoute('participants.tires.index', $participant);

        $freshTire = $tire->fresh();

        $this->assertNotEquals($tire->code, $freshTire->code);
        $this->assertEquals('TNEW', $freshTire->code);

        $response->assertSessionHas('flash.banner', 'Tire code updated.');

    }

    public function test_tire_can_be_updated_with_existing_code()
    {
        $user = User::factory()->tireagent()->create();

        $race = Race::factory()->create();

        $participant = Participant::factory()
            ->has(Tire::factory()->count(2)->state([
                'race_id' => $race->getKey(),
            ]))
            ->create([
                'race_id' => $race->getKey(),
                'championship_id' => $race->championship->getKey(),
            ]);

        $tire = $participant->tires->first();
        $code = $participant->tires->last()->code;

        $response = $this
            ->actingAs($user)
            ->from(route('tires.edit', $tire))
            ->put(route('tires.update', $tire), [
                'tire' => $code,
            ]);

        $response->assertRedirectToRoute('tires.edit', $tire);

        $response->assertSessionHasErrors(['tire' => 'The tire has already been taken.']);

        $freshTire = $tire->fresh();

        $this->assertEquals($tire->code, $freshTire->code);
    }
}

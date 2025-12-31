<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\Race;
use App\Models\Transponder;
use App\Models\User;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class ParticipantTransponderControllerTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_transponder_creation_page_loads()
    {
        $user = User::factory()->timekeeper()->create();

        $participant = Participant::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('participants.transponders.create', $participant));

        $response->assertSuccessful();

        $response->assertViewHas('participant', $participant);

        $response->assertViewHas('race', $participant->race);

        $response->assertViewHas('transponderLimit', 1);

    }

    public function test_transponder_can_be_assigned()
    {
        $user = User::factory()->timekeeper()->create();

        $participant = Participant::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('participants.transponders.create', $participant))
            ->post(route('participants.transponders.store', $participant), [
                'transponders' => ['5'],
            ]);

        $response->assertSessionHasNoErrors();

        $response->assertRedirectToRoute('races.participants.index', $participant->race);

        $transponder = $participant->fresh()->transponders()->first();

        $this->assertNotNull($transponder);
        $this->assertEquals(5, $transponder->code);
        $this->assertEquals($participant->race_id, $transponder->race_id);
    }

    public function test_transponder_not_assigned_if_already_attached_to_another_participant()
    {
        $user = User::factory()->timekeeper()->create();

        $race = Race::factory()->create();

        $participant = Participant::factory()
            ->has(Transponder::factory()->state([
                'race_id' => $race->getKey(),
            ]))
            ->create([
                'race_id' => $race->getKey(),
                'championship_id' => $race->championship->getKey(),
            ]);

        $response = $this
            ->actingAs($user)
            ->from(route('participants.transponders.create', $participant))
            ->post(route('participants.transponders.store', $participant), [
                'transponders' => [$participant->transponders()->first()->code],
            ]);

        $response->assertSessionHasErrors('transponders.0');

        $response->assertRedirectToRoute('participants.transponders.create', $participant);

        $this->assertEquals(1, $participant->fresh()->transponders()->count());
    }

    public function test_transponder_with_letters_is_rejected()
    {
        $user = User::factory()->timekeeper()->create();

        $participant = Participant::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('participants.transponders.create', $participant))
            ->post(route('participants.transponders.store', $participant), [
                'transponders' => ['ABC123'],
            ]);

        $response->assertSessionHasErrors('transponders.0');

        $response->assertRedirectToRoute('participants.transponders.create', $participant);

        $this->assertEquals(0, $participant->fresh()->transponders()->count());
    }
    
    public function test_transponder_code_required()
    {
        $user = User::factory()->timekeeper()->create();

        $participant = Participant::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('participants.transponders.create', $participant))
            ->post(route('participants.transponders.store', $participant), [
                'transponders' => [''],
            ]);

        $response->assertSessionHasErrors('transponders.0');

        $response->assertRedirectToRoute('participants.transponders.create', $participant);

        $this->assertEquals(0, $participant->fresh()->transponders()->count());
    }

    public function test_transponder_can_be_updated()
    {
        $user = User::factory()->timekeeper()->create();

        $race = Race::factory()->create();

        $participant = Participant::factory()
            ->has(Transponder::factory()->state([
                'code' => '123',
                'race_id' => $race->getKey(),
            ]))
            ->create([
                'race_id' => $race->getKey(),
                'championship_id' => $race->championship->getKey(),
            ]);

        $transponder = $participant->transponders()->first();

        $response = $this
            ->actingAs($user)
            ->from(route('transponders.edit', $transponder))
            ->patch(route('transponders.update', $transponder), [
                'transponder' => '456',
            ]);

        $response->assertSessionHasNoErrors();

        $response->assertRedirectToRoute('races.participants.index', $participant->race);

        $this->assertEquals('456', $transponder->fresh()->code);
    }

    public function test_transponder_update_rejects_letters()
    {
        $user = User::factory()->timekeeper()->create();

        $race = Race::factory()->create();

        $participant = Participant::factory()
            ->has(Transponder::factory()->state([
                'code' => '123',
                'race_id' => $race->getKey(),
            ]))
            ->create([
                'race_id' => $race->getKey(),
                'championship_id' => $race->championship->getKey(),
            ]);

        $transponder = $participant->transponders()->first();

        $response = $this
            ->actingAs($user)
            ->from(route('transponders.edit', $transponder))
            ->patch(route('transponders.update', $transponder), [
                'transponder' => 'ABC456',
            ]);

        $response->assertSessionHasErrors('transponder');

        $response->assertRedirectToRoute('transponders.edit', $transponder);

        $this->assertEquals('123', $transponder->fresh()->code);
    }

    public function test_transponder_update_rejects_duplicate_code_in_same_race()
    {
        $user = User::factory()->timekeeper()->create();

        $race = Race::factory()->create();

        $participant1 = Participant::factory()
            ->has(Transponder::factory()->state([
                'code' => '123',
                'race_id' => $race->getKey(),
            ]))
            ->create([
                'race_id' => $race->getKey(),
                'championship_id' => $race->championship->getKey(),
            ]);

        $participant2 = Participant::factory()
            ->has(Transponder::factory()->state([
                'code' => '456',
                'race_id' => $race->getKey(),
            ]))
            ->create([
                'race_id' => $race->getKey(),
                'championship_id' => $race->championship->getKey(),
            ]);

        $transponder = $participant2->transponders()->first();

        $response = $this
            ->actingAs($user)
            ->from(route('transponders.edit', $transponder))
            ->patch(route('transponders.update', $transponder), [
                'transponder' => '123',
            ]);

        $response->assertSessionHasErrors('transponder');

        $response->assertRedirectToRoute('transponders.edit', $transponder);

        $this->assertEquals('456', $transponder->fresh()->code);
    }

    public function test_transponder_can_be_removed()
    {
        $user = User::factory()->timekeeper()->create();

        $race = Race::factory()->create();

        $participant = Participant::factory()
            ->has(Transponder::factory()->state([
                'code' => '123',
                'race_id' => $race->getKey(),
            ]))
            ->create([
                'race_id' => $race->getKey(),
                'championship_id' => $race->championship->getKey(),
            ]);

        $transponder = $participant->transponders()->first();

        $this->assertEquals(1, $participant->transponders()->count());

        $response = $this
            ->actingAs($user)
            ->from(route('participants.transponders.index', $participant))
            ->delete(route('transponders.destroy', $transponder));

        $response->assertSessionHasNoErrors();

        $response->assertRedirectToRoute('participants.transponders.index', $participant);

        $this->assertEquals(0, $participant->fresh()->transponders()->count());

        $this->assertDatabaseMissing('transponders', [
            'id' => $transponder->id,
        ]);
    }
}

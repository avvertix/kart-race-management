<?php

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ParticipantTransponderControllerTest extends TestCase
{
    use RefreshDatabase;

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
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Operations;

use App\Actions\BackfillParticipantRacerHash;
use App\Models\Participant;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class BackfillParticipantRacerHashTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_participant_racer_hash_is_backfilled_when_null()
    {
        $participant = Participant::factory()->create([
            'driver_licence' => 'ABCDEFGH1234',
            'racer_hash' => null,
        ]);

        $action = new BackfillParticipantRacerHash();
        $updated = $action();

        $this->assertEquals(1, $updated);

        $updatedParticipant = $participant->fresh();

        $this->assertEquals('ABCDEFGH', $updatedParticipant->racer_hash);
    }

    public function test_participant_racer_hash_is_backfilled_when_empty()
    {
        $participant = Participant::factory()->create([
            'driver_licence' => 'XYZ12345ABCD',
            'racer_hash' => '',
        ]);

        $action = new BackfillParticipantRacerHash();
        $updated = $action();

        $this->assertEquals(1, $updated);

        $updatedParticipant = $participant->fresh();

        $this->assertEquals('XYZ12345', $updatedParticipant->racer_hash);
    }

    public function test_participant_racer_hash_not_updated_when_already_set()
    {
        $participant = Participant::factory()->create([
            'driver_licence' => 'NEWVALUE1234',
            'racer_hash' => 'OLDVALUE',
        ]);

        $action = new BackfillParticipantRacerHash();
        $updated = $action();

        $this->assertEquals(0, $updated);

        $updatedParticipant = $participant->fresh();

        $this->assertEquals('OLDVALUE', $updatedParticipant->racer_hash);
    }

    public function test_multiple_participants_are_backfilled()
    {
        Participant::factory()->create([
            'driver_licence' => 'FIRST123ABCD',
            'racer_hash' => null,
        ]);

        Participant::factory()->create([
            'driver_licence' => 'SECOND12ABCD',
            'racer_hash' => '',
        ]);

        Participant::factory()->create([
            'driver_licence' => 'THIRD123ABCD',
            'racer_hash' => 'EXISTING',
        ]);

        $action = new BackfillParticipantRacerHash();
        $updated = $action();

        $this->assertEquals(2, $updated);

        $participants = Participant::all();

        $this->assertEquals('FIRST123', $participants[0]->racer_hash);
        $this->assertEquals('SECOND12', $participants[1]->racer_hash);
        $this->assertEquals('EXISTING', $participants[2]->racer_hash);
    }
}

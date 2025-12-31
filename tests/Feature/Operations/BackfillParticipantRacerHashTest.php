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
        
        $action();

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
        
        $action();

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
        
        $action();

        $updatedParticipant = $participant->fresh();

        $this->assertEquals('OLDVALUE', $updatedParticipant->racer_hash);
    }

    public function test_multiple_participants_are_backfilled()
    {
        $p_one = Participant::factory()->create([
            'driver_licence' => 'FIRST123ABCD',
            'racer_hash' => null,
        ]);

        $p_two = Participant::factory()->create([
            'driver_licence' => 'SECOND12ABCD',
            'racer_hash' => '',
        ]);

        $p_three = Participant::factory()->create([
            'driver_licence' => 'THIRD123ABCD',
            'racer_hash' => 'EXISTING',
        ]);

        $action = new BackfillParticipantRacerHash();
        
        $action();

        $this->assertEquals('FIRST123', $p_one->fresh()->racer_hash);
        $this->assertEquals('SECOND12', $p_two->fresh()->racer_hash);
        $this->assertEquals('EXISTING', $p_three->fresh()->racer_hash);
    }
}

<?php

namespace Tests\Feature;

use App\Actions\DeleteParticipant;
use App\Models\Participant;
use App\Models\TrashedParticipant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Arr;
use Tests\TestCase;

class DeleteParticipantTest extends TestCase
{
    use RefreshDatabase;

    public function test_participant_can_be_trashed()
    {

        $participant = Participant::factory()->create();

        $trashedParticipant = (new DeleteParticipant)($participant);

        $this->assertInstanceOf(TrashedParticipant::class, $trashedParticipant);

        $this->assertEquals(collect($participant->toArray())->forget(['created_at', 'updated_at']) , collect($trashedParticipant->toArray())->forget(['created_at', 'updated_at']));

        $this->assertNull($participant->fresh());
        $this->assertNotNull($trashedParticipant->fresh());
    }
}

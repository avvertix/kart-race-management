<?php

namespace Tests\Feature;

use App\Actions\DeleteParticipant;
use App\Models\Bonus;
use App\Models\Category;
use App\Models\Participant;
use App\Models\Race;
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

        $this->assertEquals((string)$participant->uuid, (string)$trashedParticipant->uuid);

        $this->assertEquals(collect($participant->toArray())->forget(['uuid', 'created_at', 'updated_at']) , collect($trashedParticipant->toArray())->forget(['uuid', 'created_at', 'updated_at']));

        $this->assertNull($participant->fresh());
        $this->assertNotNull($trashedParticipant->fresh());
    }

    public function test_participant_with_new_category_can_be_trashed()
    {

        $race = Race::factory()->create();

        $category = Category::factory()->recycle($race->championship)->create();

        $participant = Participant::factory()->category($category)->create([
            'bib' => 100,
            'championship_id' => $race->championship->getKey(),
            'race_id' => $race->getKey(),
        ]);

        $trashedParticipant = (new DeleteParticipant)($participant);

        $this->assertInstanceOf(TrashedParticipant::class, $trashedParticipant);

        $this->assertEquals((string)$participant->uuid, (string)$trashedParticipant->uuid);

        $this->assertEquals(collect($participant->toArray())->forget(['uuid', 'created_at', 'updated_at']) , collect($trashedParticipant->toArray())->forget(['uuid', 'created_at', 'updated_at']));

        $this->assertNull($participant->fresh());
        $this->assertNotNull($trashedParticipant->fresh());
        $this->assertTrue($trashedParticipant->fresh()->racingCategory->is($category));
    }

    public function test_participant_with_bonus_can_be_trashed()
    {
        $race = Race::factory()->create();

        $category = Category::factory()->recycle($race->championship)->create();

        $bonus = Bonus::factory()
            ->recycle($race->championship)
            ->create();

        $participant = Participant::factory()->category($category)->create([
            'bib' => 100,
            'championship_id' => $race->championship->getKey(),
            'race_id' => $race->getKey(),
        ]);

        $participant->bonuses()->attach($bonus);

        $trashedParticipant = (new DeleteParticipant)($participant);

        $this->assertInstanceOf(TrashedParticipant::class, $trashedParticipant);

        $this->assertEquals((string)$participant->uuid, (string)$trashedParticipant->uuid);

        $this->assertEquals(collect($participant->toArray())->forget(['uuid', 'created_at', 'updated_at']) , collect($trashedParticipant->toArray())->forget(['uuid', 'created_at', 'updated_at']));

        $this->assertNull($participant->fresh());
        $this->assertNotNull($trashedParticipant->fresh());
        $this->assertTrue($trashedParticipant->fresh()->racingCategory->is($category));
    }
}

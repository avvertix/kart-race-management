<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\LinkParticipantResults;
use App\Models\Participant;
use App\Models\ParticipantResult;
use App\Models\Race;
use App\Models\RunResult;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class LinkParticipantResultsTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_links_participant_result_via_racer_hash(): void
    {
        $race = Race::factory()->create();

        $participant = Participant::factory()->create([
            'race_id' => $race->getKey(),
            'championship_id' => $race->championship_id,
        ]);

        $runResult = RunResult::factory()->create([
            'race_id' => $race->getKey(),
        ]);

        $participantResult = ParticipantResult::factory()->create([
            'run_result_id' => $runResult->getKey(),
            'racer_hash' => $participant->racer_hash,
            'participant_id' => null,
            'category_id' => null,
        ]);

        LinkParticipantResults::dispatchSync($runResult);

        $participantResult->refresh();

        $this->assertEquals($participant->id, $participantResult->participant_id);
        $this->assertEquals($participant->category_id, $participantResult->category_id);
    }

    public function test_does_not_link_when_racer_hash_does_not_match(): void
    {
        $race = Race::factory()->create();

        Participant::factory()->create([
            'race_id' => $race->getKey(),
            'championship_id' => $race->championship_id,
        ]);

        $runResult = RunResult::factory()->create([
            'race_id' => $race->getKey(),
        ]);

        $participantResult = ParticipantResult::factory()->create([
            'run_result_id' => $runResult->getKey(),
            'racer_hash' => 'nonexist',
            'participant_id' => null,
            'category_id' => null,
        ]);

        LinkParticipantResults::dispatchSync($runResult);

        $participantResult->refresh();

        $this->assertNull($participantResult->participant_id);
        $this->assertNull($participantResult->category_id);
    }

    public function test_does_not_link_participant_from_another_race(): void
    {
        $race = Race::factory()->create();
        $otherRace = Race::factory()->create();

        $participant = Participant::factory()->create([
            'race_id' => $otherRace->getKey(),
            'championship_id' => $otherRace->championship_id,
        ]);

        $runResult = RunResult::factory()->create([
            'race_id' => $race->getKey(),
        ]);

        $participantResult = ParticipantResult::factory()->create([
            'run_result_id' => $runResult->getKey(),
            'racer_hash' => $participant->racer_hash,
            'participant_id' => null,
            'category_id' => null,
        ]);

        LinkParticipantResults::dispatchSync($runResult);

        $participantResult->refresh();

        $this->assertNull($participantResult->participant_id);
        $this->assertNull($participantResult->category_id);
    }

    public function test_does_not_overwrite_already_linked_results(): void
    {
        $race = Race::factory()->create();

        $participant = Participant::factory()->create([
            'race_id' => $race->getKey(),
            'championship_id' => $race->championship_id,
        ]);

        $otherParticipant = Participant::factory()->create([
            'race_id' => $race->getKey(),
            'championship_id' => $race->championship_id,
        ]);

        $runResult = RunResult::factory()->create([
            'race_id' => $race->getKey(),
        ]);

        $participantResult = ParticipantResult::factory()->create([
            'run_result_id' => $runResult->getKey(),
            'racer_hash' => $participant->racer_hash,
            'participant_id' => $otherParticipant->id,
            'category_id' => $otherParticipant->category_id,
        ]);

        LinkParticipantResults::dispatchSync($runResult);

        $participantResult->refresh();

        $this->assertEquals($otherParticipant->id, $participantResult->participant_id);
        $this->assertEquals($otherParticipant->category_id, $participantResult->category_id);
    }

    public function test_job_dispatched_on_result_upload(): void
    {
        \Illuminate\Support\Facades\Queue::fake();
        \Illuminate\Support\Facades\Storage::fake('race-results');

        $user = \App\Models\User::factory()->admin()->create();
        $race = Race::factory()->create();

        $xmlFile = new \Illuminate\Http\UploadedFile(
            path: base_path('tests/fixtures/race-1-results.xml'),
            originalName: '3 - GARA 1   RACE 1 - results.xml',
            test: true,
        );

        $this
            ->actingAs($user)
            ->post(route('races.results.store', $race), [
                'files' => [$xmlFile],
            ]);

        \Illuminate\Support\Facades\Queue::assertPushed(LinkParticipantResults::class);
    }
}

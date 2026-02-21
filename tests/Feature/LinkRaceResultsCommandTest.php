<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\LinkParticipantResults;
use App\Models\Race;
use App\Models\RunResult;
use Illuminate\Support\Facades\Queue;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class LinkRaceResultsCommandTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_dispatches_link_jobs_for_all_run_results(): void
    {
        Queue::fake();

        $race = Race::factory()->create();

        $runResults = RunResult::factory()->count(3)->create([
            'race_id' => $race->getKey(),
        ]);

        $this->artisan('race:link-results', ['race' => $race->uuid])
            ->assertSuccessful()
            ->expectsOutputToContain('Dispatching link jobs for 3 run result(s)');

        Queue::assertPushed(LinkParticipantResults::class, 3);
    }

    public function test_handles_race_with_no_results(): void
    {
        $race = Race::factory()->create();

        $this->artisan('race:link-results', ['race' => $race->uuid])
            ->assertSuccessful()
            ->expectsOutputToContain('No run results found');
    }

    public function test_fails_with_invalid_race_uuid(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->artisan('race:link-results', ['race' => 'invalid-uuid']);
    }
}

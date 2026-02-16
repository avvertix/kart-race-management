<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ParticipantResult;
use App\Models\Race;
use App\Models\RunResult;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class PublicRaceResultControllerTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_can_view_race_results_index_without_authentication(): void
    {
        $race = Race::factory()->create();

        $runResult = RunResult::factory()->published()->create([
            'race_id' => $race->getKey(),
        ]);

        ParticipantResult::factory()->count(3)->create([
            'run_result_id' => $runResult->getKey(),
        ]);

        $response = $this->get(route('public.races.results.index', $race));

        $response->assertSuccessful();
        $response->assertViewHas('groupedRunResults');

        $groupedRunResults = $response->viewData('groupedRunResults');
        $this->assertEquals(1, $groupedRunResults->count());
        $this->assertEquals(3, $groupedRunResults->first()->first()->participant_results_count);
    }

    public function test_unpublished_results_are_not_listed_on_index(): void
    {
        $race = Race::factory()->create();

        RunResult::factory()->create([
            'race_id' => $race->getKey(),
            'published_at' => null,
        ]);

        RunResult::factory()->published()->create([
            'race_id' => $race->getKey(),
        ]);

        $response = $this->get(route('public.races.results.index', $race));

        $response->assertSuccessful();

        $groupedRunResults = $response->viewData('groupedRunResults');
        $this->assertEquals(1, $groupedRunResults->flatten()->count());
    }

    public function test_can_view_single_published_result(): void
    {
        $runResult = RunResult::factory()->published()->create();

        ParticipantResult::factory()->count(5)->create([
            'run_result_id' => $runResult->getKey(),
        ]);

        $response = $this->get(route('public.results.show', $runResult));

        $response->assertSuccessful();
        $response->assertViewHas('runResult');
        $response->assertViewHas('participantResults');

        $participantResults = $response->viewData('participantResults');
        $this->assertEquals(5, $participantResults->count());
    }

    public function test_cannot_view_unpublished_result(): void
    {
        $runResult = RunResult::factory()->create([
            'published_at' => null,
        ]);

        $response = $this->get(route('public.results.show', $runResult));

        $response->assertNotFound();
    }

    public function test_race_with_no_published_results_shows_empty_state(): void
    {
        $race = Race::factory()->create();

        $response = $this->get(route('public.races.results.index', $race));

        $response->assertSuccessful();

        $groupedRunResults = $response->viewData('groupedRunResults');
        $this->assertEquals(0, $groupedRunResults->count());
        $response->assertSee(__('No published results.'));
    }
}

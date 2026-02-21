<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\ParticipantResult;
use App\Models\Race;
use App\Models\RunResult;
use App\Models\RunType;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class ResultRaceControllerTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_index_requires_authentication(): void
    {
        $race = Race::factory()->create();

        $response = $this->get(route('races.results.index', $race));

        $response->assertRedirectToRoute('login');
    }

    public function test_index_shows_run_results(): void
    {
        $user = User::factory()->admin()->create();

        $race = Race::factory()->create();

        $runResult = RunResult::factory()->create([
            'race_id' => $race->getKey(),
        ]);

        ParticipantResult::factory()->count(3)->create([
            'run_result_id' => $runResult->getKey(),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('races.results.index', $race));

        $response->assertSuccessful();
        $response->assertViewHas('runResults');

        $runResults = $response->viewData('runResults');
        $this->assertEquals(1, $runResults->count());
        $this->assertEquals(3, $runResults->first()->participant_results_count);
    }

    public function test_create_page_loads(): void
    {
        $user = User::factory()->admin()->create();
        $race = Race::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('races.results.create', $race));

        $response->assertSuccessful();
        $response->assertViewHas('race', $race);
    }

    public function test_store_with_valid_xml_creates_records(): void
    {
        Storage::fake('race-results');

        $user = User::factory()->admin()->create();
        $race = Race::factory()->create();

        $xmlFile = new UploadedFile(
            path: base_path('tests/fixtures/race-1-results.xml'),
            originalName: '3 - GARA 1   RACE 1 - results.xml',
            test: true,
        );

        $response = $this
            ->actingAs($user)
            ->post(route('races.results.store', $race), [
                'files' => [$xmlFile],
            ]);

        $response->assertRedirect(route('races.results.index', $race));
        $response->assertSessionHas('flash.banner');

        $this->assertDatabaseCount('run_results', 1);
        $this->assertDatabaseHas('run_results', [
            'race_id' => $race->getKey(),
        ]);
        $this->assertDatabaseCount('participant_results', 10);
    }

    public function test_store_with_multiple_xml_files(): void
    {
        Storage::fake('race-results');

        $user = User::factory()->admin()->create();
        $race = Race::factory()->create();

        $raceFile = new UploadedFile(
            path: base_path('tests/fixtures/race-1-results.xml'),
            originalName: '3 - GARA 1   RACE 1 - results.xml',
            test: true,
        );

        $qualifyingFile = new UploadedFile(
            path: base_path('tests/fixtures/qualifying-results.xml'),
            originalName: '1 - QUALIFICHE   QUALIFYING - results.xml',
            test: true,
        );

        $response = $this
            ->actingAs($user)
            ->post(route('races.results.store', $race), [
                'files' => [$raceFile, $qualifyingFile],
            ]);

        $response->assertRedirect(route('races.results.index', $race));

        $this->assertDatabaseCount('run_results', 2);
    }

    public function test_store_rejects_non_xml_files(): void
    {
        Storage::fake('race-results');

        $user = User::factory()->admin()->create();
        $race = Race::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('races.results.store', $race), [
                'files' => [UploadedFile::fake()->create('results.txt', 100)],
            ]);

        $response->assertSessionHasErrors('files.0');
        $this->assertDatabaseCount('run_results', 0);
    }

    public function test_store_rejects_missing_files(): void
    {
        $user = User::factory()->admin()->create();
        $race = Race::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('races.results.store', $race), [
                'files' => [],
            ]);

        $response->assertSessionHasErrors('files');
    }

    public function test_show_displays_participant_results(): void
    {
        $user = User::factory()->admin()->create();

        $race = Race::factory()->create();

        $runResult = RunResult::factory()->create([
            'race_id' => $race->getKey(),
        ]);

        ParticipantResult::factory()->count(5)->create([
            'run_result_id' => $runResult->getKey(),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('results.show', $runResult));

        $response->assertSuccessful();
        $response->assertViewHas('runResult');
        $response->assertViewHas('participantResults');

        $participantResults = $response->viewData('participantResults');
        $this->assertEquals(5, $participantResults->count());
    }

    public function test_destroy_removes_records_and_file(): void
    {
        Storage::fake('race-results');

        $user = User::factory()->admin()->create();
        $race = Race::factory()->create();

        $filePath = $race->uuid.'/test-results.xml';
        Storage::disk('race-results')->put($filePath, '<table></table>');

        $runResult = RunResult::factory()->create([
            'race_id' => $race->getKey(),
            'file_name' => $filePath,
        ]);

        ParticipantResult::factory()->count(3)->create([
            'run_result_id' => $runResult->getKey(),
        ]);

        $response = $this
            ->actingAs($user)
            ->delete(route('results.destroy', $runResult));

        $response->assertRedirect(route('races.results.index', $race));
        $response->assertSessionHas('flash.banner');

        $this->assertDatabaseCount('run_results', 0);
        $this->assertDatabaseCount('participant_results', 0);

        Storage::disk('race-results')->assertMissing($filePath);
    }

    public function test_timekeeper_cannot_upload_results(): void
    {
        $user = User::factory()->timekeeper()->create();
        $race = Race::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('races.results.store', $race), [
                'files' => [],
            ]);

        $response->assertForbidden();
    }

    public function test_timekeeper_cannot_delete_results(): void
    {
        $user = User::factory()->timekeeper()->create();

        $runResult = RunResult::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete(route('results.destroy', $runResult));

        $response->assertForbidden();
    }

    public function test_toggle_publish_publishes_result(): void
    {
        $user = User::factory()->admin()->create();

        $runResult = RunResult::factory()->create([
            'published_at' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('results.toggle-publish', $runResult));

        $response->assertRedirect();
        $response->assertSessionHas('flash.banner');

        $runResult->refresh();
        $this->assertNotNull($runResult->published_at);
        $this->assertTrue($runResult->isPublished());
    }

    public function test_toggle_publish_unpublishes_result(): void
    {
        $user = User::factory()->admin()->create();

        $runResult = RunResult::factory()->published()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('results.toggle-publish', $runResult));

        $response->assertRedirect();
        $response->assertSessionHas('flash.banner');

        $runResult->refresh();
        $this->assertNull($runResult->published_at);
        $this->assertFalse($runResult->isPublished());
    }

    public function test_toggle_publish_requires_authorization(): void
    {
        $user = User::factory()->timekeeper()->create();

        $runResult = RunResult::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('results.toggle-publish', $runResult));

        $response->assertForbidden();
    }

    public function test_edit_form_shows_run_result(): void
    {
        $user = User::factory()->admin()->create();
        $race = Race::factory()->create();

        $runResult = RunResult::factory()->create([
            'race_id' => $race->getKey(),
        ]);

        ParticipantResult::factory()->count(3)->create([
            'run_result_id' => $runResult->getKey(),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('results.edit', $runResult));

        $response->assertSuccessful();
        $response->assertViewHas('runResult');
        $response->assertViewHas('participantResults');
        $response->assertSee($runResult->title);
    }

    public function test_edit_requires_authorization(): void
    {
        $user = User::factory()->timekeeper()->create();

        $runResult = RunResult::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('results.edit', $runResult));

        $response->assertForbidden();
    }

    public function test_run_result_title_and_session_updated(): void
    {
        $user = User::factory()->admin()->create();
        $race = Race::factory()->create();

        $runResult = RunResult::factory()->create([
            'race_id' => $race->getKey(),
            'title' => 'Old Title',
            'run_type' => RunType::RACE_1,
        ]);

        $response = $this
            ->actingAs($user)
            ->put(route('results.update', $runResult), [
                'title' => 'New Title',
                'run_type' => RunType::QUALIFY->value,
                'participant_results' => [],
            ]);

        $response->assertRedirect(route('results.show', $runResult));
        $response->assertSessionHas('flash.banner');

        $runResult->refresh();
        $this->assertEquals('New Title', $runResult->title);
        $this->assertEquals(RunType::QUALIFY, $runResult->run_type);
    }

    public function test_participant_result_fields_updated(): void
    {
        $user = User::factory()->admin()->create();
        $race = Race::factory()->create();

        $runResult = RunResult::factory()->create([
            'race_id' => $race->getKey(),
        ]);

        $participantResult = ParticipantResult::factory()->create([
            'run_result_id' => $runResult->getKey(),
            'bib' => 10,
            'name' => 'Old Name',
            'category' => 'Old Category',
            'best_lap_time' => '00:01:00.000',
        ]);

        $response = $this
            ->actingAs($user)
            ->put(route('results.update', $runResult), [
                'title' => $runResult->title,
                'run_type' => $runResult->run_type->value,
                'participant_results' => [
                    [
                        'id' => $participantResult->getKey(),
                        'bib' => 99,
                        'name' => 'New Name',
                        'category' => 'New Category',
                        'participant_id' => null,
                    ],
                ],
            ]);

        $response->assertRedirect(route('results.show', $runResult));

        $participantResult->refresh();
        $this->assertEquals(99, $participantResult->bib);
        $this->assertEquals('New Name', $participantResult->name);
        $this->assertEquals('New Category', $participantResult->category);
        $this->assertEquals('00:01:00.000', $participantResult->best_lap_time);
    }

    public function test_participant_result_linked_to_participant(): void
    {
        $user = User::factory()->admin()->create();
        $race = Race::factory()->create();

        $participant = Participant::factory()->create([
            'race_id' => $race->getKey(),
        ]);

        $runResult = RunResult::factory()->create([
            'race_id' => $race->getKey(),
        ]);

        $participantResult = ParticipantResult::factory()->create([
            'run_result_id' => $runResult->getKey(),
            'participant_id' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->put(route('results.update', $runResult), [
                'title' => $runResult->title,
                'run_type' => $runResult->run_type->value,
                'participant_results' => [
                    [
                        'id' => $participantResult->getKey(),
                        'bib' => $participantResult->bib,
                        'name' => $participantResult->name,
                        'category' => $participantResult->category,
                        'participant_id' => $participant->getKey(),
                    ],
                ],
            ]);

        $response->assertRedirect(route('results.show', $runResult));

        $participantResult->refresh();
        $this->assertEquals($participant->getKey(), $participantResult->participant_id);
    }

    public function test_participant_result_unlinked(): void
    {
        $user = User::factory()->admin()->create();
        $race = Race::factory()->create();

        $participant = Participant::factory()->create([
            'race_id' => $race->getKey(),
        ]);

        $runResult = RunResult::factory()->create([
            'race_id' => $race->getKey(),
        ]);

        $participantResult = ParticipantResult::factory()->create([
            'run_result_id' => $runResult->getKey(),
            'participant_id' => $participant->getKey(),
        ]);

        $response = $this
            ->actingAs($user)
            ->put(route('results.update', $runResult), [
                'title' => $runResult->title,
                'run_type' => $runResult->run_type->value,
                'participant_results' => [
                    [
                        'id' => $participantResult->getKey(),
                        'bib' => $participantResult->bib,
                        'name' => $participantResult->name,
                        'category' => $participantResult->category,
                        'participant_id' => null,
                    ],
                ],
            ]);

        $response->assertRedirect(route('results.show', $runResult));

        $participantResult->refresh();
        $this->assertNull($participantResult->participant_id);
    }

    public function test_update_requires_authorization(): void
    {
        $user = User::factory()->timekeeper()->create();

        $runResult = RunResult::factory()->create();

        $response = $this
            ->actingAs($user)
            ->put(route('results.update', $runResult), [
                'title' => 'Test',
                'run_type' => RunType::RACE_1->value,
                'participant_results' => [],
            ]);

        $response->assertForbidden();
    }
}

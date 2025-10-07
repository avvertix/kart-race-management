<?php

declare(strict_types=1);

namespace Tests\Feature\Exports;

use App\Exports\ChampionshipParticipantsExport;
use App\Models\Championship;
use App\Models\Participant;
use App\Models\User;
use Maatwebsite\Excel\Facades\Excel;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class ExportChampionshipParticipantsTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_export_requires_authentication()
    {
        $championship = Championship::factory()->create();

        $response = $this->get(route('championships.export.participants', $championship));

        $response->assertRedirect(route('login'));
    }

    public function test_export_forbidden_for_tireagent()
    {
        $user = User::factory()->tireagent()->create();

        $championship = Championship::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('championships.export.participants', $championship));

        $response->assertForbidden();
    }

    public function test_export_forbidden_for_timekeeper()
    {
        $user = User::factory()->timekeeper()->create();

        $championship = Championship::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('championships.export.participants', $championship));

        $response->assertForbidden();
    }

    public function test_export_forbidden_for_racemanager()
    {
        $user = User::factory()->racemanager()->create();

        $championship = Championship::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('championships.export.participants', $championship));

        $response->assertForbidden();
    }

    public function test_export_lists_participants()
    {
        $user = User::factory()->organizer()->create();

        $championship = Championship::factory()->create(['title' => 'Test Championship']);

        $participant = Participant::factory()
            ->recycle($championship)
            ->category()
            ->create();

        Excel::fake();

        $response = $this
            ->actingAs($user)
            ->get(route('championships.export.participants', $championship));

        $expected_filename = 'participants-test-championship.xlsx';

        Excel::assertDownloaded($expected_filename, function (ChampionshipParticipantsExport $export) use ($championship, $participant) {

            $queryResults = $export->query()->get();

            return $export->map($participant) === [
                $participant->bib,
                $participant->fullName,
                $participant->email,
            ] && $queryResults->count() === 1 && $queryResults->first()->is($participant) && $queryResults->first()->championship->is($championship);
        });

    }
}

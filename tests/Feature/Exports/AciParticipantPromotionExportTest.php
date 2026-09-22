<?php

declare(strict_types=1);

namespace Tests\Feature\Exports;

use App\Exports\AciParticipantPromotionExport;
use App\Models\Participant;
use App\Models\Race;
use App\Models\RaceType;
use App\Models\Transponder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class AciParticipantPromotionExportTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_export_requires_authentication()
    {
        $race = Race::factory()->create();

        $response = $this->get(route('races.export.aci', $race));

        $response->assertRedirect(route('login'));
    }

    public function test_export_forbidden_for_tireagent()
    {
        $user = User::factory()->tireagent()->create();

        $race = Race::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('races.export.aci', $race));

        $response->assertForbidden();
    }

    public function test_aci_export_lists_participants()
    {
        $user = User::factory()->organizer()->create();

        $race = Race::factory()
            ->create([
                'event_start_at' => Carbon::parse('2023-02-28'),
                'title' => 'Race title',
                'type' => RaceType::NATIONAL,
            ]);

        $participant = Participant::factory()
            ->recycle($race)
            ->recycle($race->championship)
            ->has(Transponder::factory()->state([
                'code' => 11,
                'race_id' => $race->getKey(),
            ]), 'transponders')
            ->category()
            ->create();

        Excel::fake();

        $response = $this
            ->actingAs($user)
            ->get(route('races.export.aci', $race));

        $expected_filename = Str::slug('ACI-'.$race->title.'-'.$race->event_start_at->toDateString()).'.xlsx';

        Excel::assertDownloaded($expected_filename, function (AciParticipantPromotionExport $export) use ($race, $participant) {

            $view = $export->view();

            $data = $view->getData();

            $html = $view->render();

            return $data['race']->is($race) && $data['participants']->isNotEmpty() && Str::contains($html, $participant->full_name)
                && Str::contains($html, $race->title);
        });

    }
}

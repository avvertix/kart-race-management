<?php

namespace Tests\Feature\Exports;

use App\Models\CompetitorLicence;
use App\Models\Participant;
use App\Models\Race;
use App\Models\Sex;
use App\Models\Transponder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Vitorccs\LaravelCsv\Helpers\CsvHelper;

class RaceParticipantsForTimingExportTest extends TestCase
{
    use RefreshDatabase;


    public function test_export_requires_authentication()
    {
        $race = Race::factory()->create();

        $response = $this->get(route('races.export.transponders', $race));

        $response->assertRedirect(route('login'));
    }
    
    public function test_export_forbidden_for_tireagent()
    {
        $user = User::factory()->tireagent()->create();

        $race = Race::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('races.export.transponders', $race));

        $response->assertForbidden();
    }

    public function test_export_participants_with_transponders()
    {
        config(['races.organizer.name' => 'Organizer name']);
        config(['categories.default' => ['category_key' => [
            'name' => 'Category Name',
            'description' => 'category description',
            'tires' => 'VEGA_MINI',
        ]]]);

        $user = User::factory()->timekeeper()->create();

        $race = Race::factory()
            ->create([
                'event_start_at' => Carbon::parse("2023-02-28"),
                'title' => 'Race title'
            ]);
        
        $participant = Participant::factory()
            ->has(Transponder::factory()->state([
                    'code' => 11,
                    'race_id' => $race->getKey()
                ]), 'transponders')
            ->create([
                'driver_licence' => 'a1234567890',
                'race_id' => $race->getKey(),
                'championship_id' => $race->championship->getKey(),
            ]);

        $vehicle = $participant->vehicles[0];

        $this->withoutExceptionHandling();
        
        $response = $this
            ->actingAs($user)
            ->get(route('races.export.transponders', $race));

        $expected_filename = "mylaps-organizer-name-2023-02-28-race-title.csv";

        $response->assertDownload($expected_filename);

        $csv = collect(str($response->streamedContent())->split('/\r?\n|\r/'))
            ->filter()
            ->map(function ($l) {
                return str_getcsv($l, ',');
            });

        $this->assertCount(2, $csv);
        $this->assertEquals([
            [
                "No",
                "Class",
                "FirstName",
                "LastName",
                "CarRegistration",
                "DriverRegistration",
                "Transponder1",
                "Transponder2",
                "Additional1",
                "Additional2",
                "Additional3",
                "Additional4",
                "Additional5",
                "Additional6",
                "Additional7",
                "Additional8",
            ],
            [
                ''.$participant->bib,
                'category_key',
                strtoupper($participant->first_name),
                strtoupper($participant->last_name),
                "a1234567", // car registration
                'a1234567', // driver registration
                "5753071", // transponder
                "", // transponder
                "",
                "",
                "",
                "",
                $participant->licence_type->localizedName(),
                strtoupper($vehicle['engine_manufacturer']),
                strtoupper($vehicle['engine_model']),
                $participant->driver['phone'] . ' - ' . ($participant->competitor['phone'] ?? ''),
            ],
        ], $csv->toArray());
    }

}

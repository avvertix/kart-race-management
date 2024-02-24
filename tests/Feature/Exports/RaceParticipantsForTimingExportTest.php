<?php

namespace Tests\Feature\Exports;

use App\Models\Category;
use App\Models\CompetitorLicence;
use App\Models\Participant;
use App\Models\Race;
use App\Models\RaceType;
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

        $user = User::factory()->timekeeper()->create();

        $race = Race::factory()
            ->create([
                'event_start_at' => Carbon::parse("2023-02-28"),
                'title' => 'Race title',
                'type' => RaceType::ZONE,
            ]);
        
        $participant = Participant::factory()
            ->recycle($race->championship)
            ->category()
            ->has(Transponder::factory()->state([
                    'code' => 11,
                    'race_id' => $race->getKey()
                ]), 'transponders')
            ->create([
                'first_name' => 'Nicàèùlò',
                'driver_licence' => 'a1234567890',
                'race_id' => $race->getKey(),
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
                return str_getcsv($l, ';');
            });

        $this->assertCount(2, $csv);
        $this->assertEquals([
            [
                "No",
                "Class",
                "LastName",
                "FirstName",
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
                $participant->categoryConfiguration()->name,
                'NICA\'E\'U\'LO\'',
                strtoupper($participant->last_name),
                "a1234567", // car registration
                'a1234567', // driver registration
                "5753071", // transponder
                "", // transponder
                "",
                "",
                "",
                "2023-02-28",
                $participant->licence_type->localizedName(),
                strtoupper($vehicle['engine_manufacturer']),
                strtoupper($vehicle['engine_model']),
                $participant->driver['phone'] . ' - ' . ($participant->competitor['phone'] ?? ''),
            ],
        ], $csv->toArray());
    }

    public function test_export_participants_with_transponders_respect_timekeeping_label_if_present()
    {
        config(['races.organizer.name' => 'Organizer name']);

        $user = User::factory()->timekeeper()->create();

        $race = Race::factory()
            ->create([
                'event_start_at' => Carbon::parse("2023-02-28"),
                'title' => 'Race title'
            ]);

        $category = Category::factory()->recycle($race->championship)->withTire()->create([
            'short_name' => 'OTHER NAME',
        ]);
        
        $participant = Participant::factory()
            ->recycle($race->championship)
            ->category($category)
            ->has(Transponder::factory()->state([
                    'code' => 11,
                    'race_id' => $race->getKey()
                ]), 'transponders')
            ->create([
                'driver_licence' => 'a1234567890',
                'race_id' => $race->getKey(),
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
                return str_getcsv($l, ';');
            });

        $this->assertCount(2, $csv);
        $this->assertEquals([
            [
                "No",
                "Class",
                "LastName",
                "FirstName",
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
                'OTHER NAME',
                strtoupper($participant->first_name),
                strtoupper($participant->last_name),
                "a1234567", // car registration
                'a1234567', // driver registration
                "5753071", // transponder
                "", // transponder
                "",
                "",
                "",
                "2023-02-28",
                $participant->licence_type->localizedName(),
                strtoupper($vehicle['engine_manufacturer']),
                strtoupper($vehicle['engine_model']),
                $participant->driver['phone'] . ' - ' . ($participant->competitor['phone'] ?? ''),
            ],
        ], $csv->toArray());
    }

    public function test_export_participants_with_out_of_zone_state()
    {
        config(['races.organizer.name' => 'Organizer name']);

        $user = User::factory()->timekeeper()->create();

        $race = Race::factory()
            ->create([
                'event_start_at' => Carbon::parse("2023-02-28"),
                'title' => 'Race title',
                'type' => RaceType::ZONE->value,
            ]);
        
        $participant = Participant::factory()
            ->recycle($race->championship)
            ->category()
            ->has(Transponder::factory()->state([
                    'code' => 11,
                    'race_id' => $race->getKey()
                ]), 'transponders')
            ->create([
                'driver_licence' => 'a1234567890',
                'race_id' => $race->getKey(),
                'properties' => ['out_of_zone' => true],
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
                return str_getcsv($l, ';');
            });

        $this->assertCount(2, $csv);
        $this->assertEquals([
            [
                "No",
                "Class",
                "LastName",
                "FirstName",
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
                $participant->categoryConfiguration()->get('name'),
                strtoupper($participant->first_name),
                strtoupper($participant->last_name),
                "a1234567", // car registration
                'a1234567', // driver registration
                "5753071", // transponder
                "", // transponder
                "",
                "",
                "Out of zone",
                "2023-02-28",
                $participant->licence_type->localizedName(),
                strtoupper($vehicle['engine_manufacturer']),
                strtoupper($vehicle['engine_model']),
                $participant->driver['phone'] . ' - ' . ($participant->competitor['phone'] ?? ''),
            ],
        ], $csv->toArray());
    }

    public function test_export_participants_does_not_include_participants_without_transponder()
    {
        config(['races.organizer.name' => 'Organizer name']);

        $user = User::factory()->timekeeper()->create();

        $race = Race::factory()
            ->create([
                'event_start_at' => Carbon::parse("2023-02-28"),
                'title' => 'Race title',
                'type' => RaceType::ZONE,
            ]);
        
        $participant = Participant::factory()
            ->recycle($race->championship)
            ->category()
            ->create([
                'driver_licence' => 'a1234567890',
                'race_id' => $race->getKey(),
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
                return str_getcsv($l, ';');
            });

        $this->assertCount(1, $csv);
        $this->assertEquals([
            [
                "No",
                "Class",
                "LastName",
                "FirstName",
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
        ], $csv->toArray());
    }

}

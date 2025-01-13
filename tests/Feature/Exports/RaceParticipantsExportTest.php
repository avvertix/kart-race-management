<?php

declare(strict_types=1);

namespace Tests\Feature\Exports;

use App\Models\CompetitorLicence;
use App\Models\Participant;
use App\Models\Race;
use App\Models\Sex;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RaceParticipantsExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_requires_authentication()
    {
        $race = Race::factory()->create();

        $response = $this->get(route('races.export.participants', $race));

        $response->assertRedirect(route('login'));
    }

    public function test_export_forbidden_for_tireagent()
    {
        $user = User::factory()->tireagent()->create();

        $race = Race::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('races.export.participants', $race));

        $response->assertForbidden();
    }

    public function test_export_forbidden_for_timekeeper()
    {
        $user = User::factory()->timekeeper()->create();

        $race = Race::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('races.export.participants', $race));

        $response->assertForbidden();
    }

    public function test_export_forbidden_for_racemanager()
    {
        $user = User::factory()->racemanager()->create();

        $race = Race::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('races.export.participants', $race));

        $response->assertForbidden();
    }

    public function test_export_lists_driver_details()
    {
        config(['races.organizer.name' => 'Organizer name']);

        $user = User::factory()->organizer()->create();

        $race = Race::factory()
            ->create([
                'event_start_at' => Carbon::parse('2023-02-28'),
                'title' => 'Race title',
            ]);

        $participant = Participant::factory()
            ->recycle($race->championship)
            ->category()
            ->create([
                'race_id' => $race->getKey(),
            ]);

        $vehicle = $participant->vehicles[0];

        $response = $this
            ->actingAs($user)
            ->get(route('races.export.participants', $race));

        $expected_filename = 'organizer-name-2023-02-28-race-title.csv';

        $response->assertDownload($expected_filename);

        $csv = collect(str($response->streamedContent())->split('/\r?\n|\r/'))
            ->filter()
            ->map(function ($l) {
                return str_getcsv($l, ';');
            });

        $this->assertCount(2, $csv);
        $this->assertEquals([
            [
                'Number',
                'Category',
                'Status',
                'Driver Name',
                'Driver Surname',
                'Driver Licence Type',
                'Driver Licence Number',
                'Driver Nationality',
                'Driver Birth date',
                'Driver Birth place',
                'Driver medical certificate expiration',
                'Driver Residence address',
                'Driver Sex',
                'Competitor Name',
                'Competitor Surname',
                'Competitor Licence Type',
                'Competitor Licence Number',
                'Competitor Nationality',
                'Competitor Birth date',
                'Competitor Birth place',
                'Competitor Residence address',
                'Mechanic Name',
                'Mechanic Licence Number',
                'Chassis Manufacturer',
                'Engine Manufacturer',
                'Engine Model',
                'Oil Manufacturer',
                'Oil Type',
                'Oil Percentage',
            ],
            [
                ''.$participant->bib,
                $participant->racingCategory->name,
                '',
                $participant->first_name,
                $participant->last_name,
                $participant->licence_type->localizedName(),
                $participant->driver['licence_number'],
                $participant->driver['nationality'],
                $participant->driver['birth_date'],
                $participant->driver['birth_place'],
                $participant->driver['medical_certificate_expiration_date'],
                __(':address :city :province :postal_code', [
                    'address' => $participant->driver['residence_address']['address'] ?? null,
                    'city' => $participant->driver['residence_address']['city'] ?? null,
                    'postal_code' => $participant->driver['residence_address']['postal_code'] ?? null,
                    'province' => $participant->driver['residence_address']['province'] ?? null,
                ]),
                Sex::from($participant->driver['sex'])->localizedName(),
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                $vehicle['chassis_manufacturer'],
                $vehicle['engine_manufacturer'],
                $vehicle['engine_model'],
                $vehicle['oil_manufacturer'],
                $vehicle['oil_type'],
                ''.$vehicle['oil_percentage'],

            ],
        ], $csv->toArray());
    }

    public function test_export_lists_participants_marked_registration_completed()
    {
        config(['races.organizer.name' => 'Organizer name']);

        $user = User::factory()->organizer()->create();

        $race = Race::factory()
            ->create([
                'event_start_at' => Carbon::parse('2023-02-28'),
                'title' => 'Race title',
            ]);

        $participant = Participant::factory()
            ->recycle($race->championship)
            ->category()
            ->markCompleted()
            ->create([
                'race_id' => $race->getKey(),
            ]);

        $vehicle = $participant->vehicles[0];

        $response = $this
            ->actingAs($user)
            ->get(route('races.export.participants', $race));

        $expected_filename = 'organizer-name-2023-02-28-race-title.csv';

        $response->assertDownload($expected_filename);

        $csv = collect(str($response->streamedContent())->split('/\r?\n|\r/'))
            ->filter()
            ->map(function ($l) {
                return str_getcsv($l, ';');
            });

        $this->assertCount(2, $csv);
        $this->assertEquals([
            [
                'Number',
                'Category',
                'Status',
                'Driver Name',
                'Driver Surname',
                'Driver Licence Type',
                'Driver Licence Number',
                'Driver Nationality',
                'Driver Birth date',
                'Driver Birth place',
                'Driver medical certificate expiration',
                'Driver Residence address',
                'Driver Sex',
                'Competitor Name',
                'Competitor Surname',
                'Competitor Licence Type',
                'Competitor Licence Number',
                'Competitor Nationality',
                'Competitor Birth date',
                'Competitor Birth place',
                'Competitor Residence address',
                'Mechanic Name',
                'Mechanic Licence Number',
                'Chassis Manufacturer',
                'Engine Manufacturer',
                'Engine Model',
                'Oil Manufacturer',
                'Oil Type',
                'Oil Percentage',
            ],
            [
                ''.$participant->bib,
                $participant->racingCategory->name,
                'completed',
                $participant->first_name,
                $participant->last_name,
                $participant->licence_type->localizedName(),
                $participant->driver['licence_number'],
                $participant->driver['nationality'],
                $participant->driver['birth_date'],
                $participant->driver['birth_place'],
                $participant->driver['medical_certificate_expiration_date'],
                __(':address :city :province :postal_code', [
                    'address' => $participant->driver['residence_address']['address'] ?? null,
                    'city' => $participant->driver['residence_address']['city'] ?? null,
                    'postal_code' => $participant->driver['residence_address']['postal_code'] ?? null,
                    'province' => $participant->driver['residence_address']['province'] ?? null,
                ]),
                Sex::from($participant->driver['sex'])->localizedName(),
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                $vehicle['chassis_manufacturer'],
                $vehicle['engine_manufacturer'],
                $vehicle['engine_model'],
                $vehicle['oil_manufacturer'],
                $vehicle['oil_type'],
                ''.$vehicle['oil_percentage'],

            ],
        ], $csv->toArray());
    }

    public function test_export_lists_includes_competitor_and_mechanic()
    {
        config(['races.organizer.name' => 'Organizer name']);

        $user = User::factory()->organizer()->create();

        $race = Race::factory()
            ->create([
                'event_start_at' => Carbon::parse('2023-02-28'),
                'title' => 'Race title',
            ]);

        $participant = Participant::factory()
            ->withCompetitor()
            ->withMechanic()
            ->category()
            ->create([
                'race_id' => $race->getKey(),
                'championship_id' => $race->championship->getKey(),
            ]);

        $vehicle = $participant->vehicles[0];

        $response = $this
            ->actingAs($user)
            ->get(route('races.export.participants', $race));

        $expected_filename = 'organizer-name-2023-02-28-race-title.csv';

        $response->assertDownload($expected_filename);

        $csv = collect(str($response->streamedContent())->split('/\r?\n|\r/'))
            ->filter()
            ->map(function ($l) {
                return str_getcsv($l, ';');
            });

        $this->assertCount(2, $csv);
        $this->assertEquals([
            [
                'Number',
                'Category',
                'Status',
                'Driver Name',
                'Driver Surname',
                'Driver Licence Type',
                'Driver Licence Number',
                'Driver Nationality',
                'Driver Birth date',
                'Driver Birth place',
                'Driver medical certificate expiration',
                'Driver Residence address',
                'Driver Sex',
                'Competitor Name',
                'Competitor Surname',
                'Competitor Licence Type',
                'Competitor Licence Number',
                'Competitor Nationality',
                'Competitor Birth date',
                'Competitor Birth place',
                'Competitor Residence address',
                'Mechanic Name',
                'Mechanic Licence Number',
                'Chassis Manufacturer',
                'Engine Manufacturer',
                'Engine Model',
                'Oil Manufacturer',
                'Oil Type',
                'Oil Percentage',
            ],
            [
                ''.$participant->bib,
                $participant->racingCategory->name,
                '',
                $participant->first_name,
                $participant->last_name,
                $participant->licence_type->localizedName(),
                $participant->driver['licence_number'],
                $participant->driver['nationality'],
                $participant->driver['birth_date'],
                $participant->driver['birth_place'],
                $participant->driver['medical_certificate_expiration_date'],
                __(':address :city :province :postal_code', [
                    'address' => $participant->driver['residence_address']['address'] ?? null,
                    'city' => $participant->driver['residence_address']['city'] ?? null,
                    'postal_code' => $participant->driver['residence_address']['postal_code'] ?? null,
                    'province' => $participant->driver['residence_address']['province'] ?? null,
                ]),
                Sex::from($participant->driver['sex'])->localizedName(),

                $participant->competitor['first_name'],
                $participant->competitor['last_name'],
                CompetitorLicence::from($participant->competitor['licence_type'])->name,
                $participant->competitor['licence_number'],
                $participant->competitor['nationality'],
                $participant->competitor['birth_date'],
                $participant->competitor['birth_place'],
                __(':address :city :province :postal_code', [
                    'address' => $participant->competitor['residence_address']['address'],
                    'city' => $participant->competitor['residence_address']['city'],
                    'postal_code' => $participant->competitor['residence_address']['postal_code'],
                    'province' => $participant->competitor['residence_address']['province'],
                ]),

                $participant->mechanic['name'],
                $participant->mechanic['licence_number'],

                $vehicle['chassis_manufacturer'],
                $vehicle['engine_manufacturer'],
                $vehicle['engine_model'],
                $vehicle['oil_manufacturer'],
                $vehicle['oil_type'],
                ''.$vehicle['oil_percentage'],

            ],
        ], $csv->toArray());
    }
}

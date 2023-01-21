<?php

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\Competitor;
use App\Models\CompetitorLicence;
use App\Models\Driver;
use App\Models\DriverLicence;
use App\Models\Mechanic;
use App\Models\Participant;
use App\Models\Race;
use App\Models\Sex;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RaceParticipantTest extends TestCase
{
    use RefreshDatabase;

    protected function setAvailableCategories()
    {
        config([
            'categories.default' => [
                'category_key' => [
                    'name' => 'CAT 1',
                    'tires' => 'T1',
                ],
            ],
        ]);
    }

    protected function generateValidDriver()
    {
        return [
            'driver_first_name' => 'John',
            'driver_last_name' => 'Racer',
            'driver_licence_number' => 'D0001',
            'driver_licence_type' => DriverLicence::LOCAL_CLUB->value,
            'driver_licence_renewed_at' => null,
            'driver_nationality' => 'Italy',
            'driver_email' => 'john@racer.local',
            'driver_phone' => '555555555',
            'driver_birth_date' => '1999-11-11',
            'driver_birth_place' => 'Milan',
            'driver_medical_certificate_expiration_date' => today()->addYear()->toDateString(),
            'driver_residence_address' => 'via dei Platani, 40',
            'driver_residence_city' => 'Milan',
            'driver_residence_province' => 'Milan',
            'driver_residence_postal_code' => '20146',
            'driver_sex' => Sex::MALE->value,
        ];
    }

    protected function generateValidCompetitor()
    {
        return [
            'competitor_first_name' => 'Parent',
            'competitor_last_name' => 'Racer',
            'competitor_licence_number' => 'C0002',
            'competitor_licence_type' => CompetitorLicence::LOCAL->value,
            'competitor_licence_renewed_at' => null,
            'competitor_nationality' => 'Italy',
            'competitor_email' => 'parent@racer.local',
            'competitor_phone' => '54444444',
            'competitor_birth_date' => '1979-11-11',
            'competitor_birth_place' => 'Milan',
            'competitor_residence_address' => 'via dei Platani, 40',
            'competitor_residence_city' => 'Milan',
            'competitor_residence_province' => 'Milan',
            'competitor_residence_postal_code' => '20146',
        ];
    }

    protected function generateValidMechanic()
    {
        return [
            'mechanic_name' => 'Mechanic Racer',
            'mechanic_licence_number' => 'M0003',
        ];
    }

    protected function generateValidVehicle()
    {
        return [
            'vehicle_chassis_manufacturer' => 'Chassis',
            'vehicle_engine_manufacturer' => 'Engine Manufacturer',
            'vehicle_engine_model' => 'Engine Model',
            'vehicle_oil_manufacturer' => 'Oil Manufacturer',
            'vehicle_oil_type' => 'Oil Type',
            'vehicle_oil_percentage' => '4',
        ];
    }

    public function test_participant_can_be_registered()
    {
        $this->setAvailableCategories();

        $user = User::factory()->racemanager()->create();

        $race = Race::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('races.participants.create', $race))
            ->post(route('races.participants.store', $race), [
                'bib' => 100,
                'category' => 'category_key',

                'driver_licence_type' => DriverLicence::LOCAL_CLUB->value,

                ...$this->generateValidDriver(),
                ...$this->generateValidCompetitor(),
                ...$this->generateValidMechanic(),
                ...$this->generateValidVehicle(),

                'consent_privacy' => true,

                'use_bonus' => false, // TODO handle use of bonus

            ]);

        $response->assertRedirectToRoute('races.participants.index', $race);

        $response->assertSessionHasNoErrors();

        $response->assertSessionHas('message', '100 John Racer added.');

        $participant = Participant::first();

        $this->assertInstanceOf(Participant::class, $participant);
        
        $this->assertEquals(100, $participant->bib);
        $this->assertEquals('category_key', $participant->category);
        $this->assertEquals('John', $participant->first_name);
        $this->assertEquals('Racer', $participant->last_name);

        $this->assertEquals([ 
            "first_name" => "John",
            "last_name" => "Racer",
            "licence_type" => 10,
            "licence_number" => "D0001",
            "licence_renewed_at" => null,
            "nationality" => "Italy",
            "email" => "john@racer.local",
            "phone" => "555555555",
            "birth_date" => "1999-11-11",
            "birth_place" => "Milan",
            "medical_certificate_expiration_date" => today()->addYear()->toDateString(),
            "residence_address" => "via dei Platani, 40 Milan Milan 20146",
            "sex" => Sex::MALE->value,
        ], $participant->driver);

        $this->assertEquals([ 
            "first_name" => "Parent",
            "last_name" => "Racer",
            "licence_type" => 10,
            "licence_number" => "C0002",
            "licence_renewed_at" => null,
            "nationality" => "Italy",
            "email" => "parent@racer.local",
            "phone" => "54444444",
            "birth_date" => "1979-11-11",
            "birth_place" => "Milan",
            "residence_address" => "via dei Platani, 40 Milan Milan 20146",
        ], $participant->competitor);

        $this->assertEquals('Mechanic Racer', $participant->mechanic['name']);
        $this->assertEquals('M0003', $participant->mechanic['licence_number']);

        $this->assertCount(1, $participant->vehicles);

        $this->assertEquals('Chassis', $participant->vehicles[0]['chassis_manufacturer']);
        $this->assertEquals('Engine Manufacturer', $participant->vehicles[0]['engine_manufacturer']);
        $this->assertEquals('Engine Model', $participant->vehicles[0]['engine_model']);
        $this->assertEquals('Oil Manufacturer', $participant->vehicles[0]['oil_manufacturer']);
        $this->assertEquals('Oil Type', $participant->vehicles[0]['oil_type']);
        $this->assertEquals('4', $participant->vehicles[0]['oil_percentage']);

    }
    public function test_participant_without_competitor_can_be_registered()
    {
        $this->setAvailableCategories();

        $user = User::factory()->racemanager()->create();

        $race = Race::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('races.participants.create', $race))
            ->post(route('races.participants.store', $race), [
                'bib' => 100,
                'category' => 'category_key',

                'driver_licence_type' => DriverLicence::LOCAL_CLUB->value,

                ...$this->generateValidDriver(),
                ...$this->generateValidMechanic(),
                ...$this->generateValidVehicle(),

                "competitor_first_name" => null,
                "competitor_last_name" => null,
                "competitor_licence_number" => null,
                "competitor_nationality" => null,
                "competitor_email" => null,
                "competitor_phone" => null,
                "competitor_birth_date" => null,
                "competitor_birth_place" => null,
                "competitor_residence_address" => null,
                "competitor_residence_postal_code" => null,
                "competitor_residence_city" => null,
                "competitor_residence_province" => null,

                'consent_privacy' => true,

                'use_bonus' => false,

            ]);

        $response->assertRedirectToRoute('races.participants.index', $race);

        $response->assertSessionHasNoErrors();

        $response->assertSessionHas('message', '100 John Racer added.');

        $participant = Participant::first();

        $this->assertInstanceOf(Participant::class, $participant);
        
        $this->assertEquals(100, $participant->bib);
        $this->assertEquals('category_key', $participant->category);
        $this->assertEquals('John', $participant->first_name);
        $this->assertEquals('Racer', $participant->last_name);

        $this->assertEquals([ 
            "first_name" => "John",
            "last_name" => "Racer",
            "licence_type" => 10,
            "licence_number" => "D0001",
            "licence_renewed_at" => null,
            "nationality" => "Italy",
            "email" => "john@racer.local",
            "phone" => "555555555",
            "birth_date" => "1999-11-11",
            "birth_place" => "Milan",
            "medical_certificate_expiration_date" => today()->addYear()->toDateString(),
            "residence_address" => "via dei Platani, 40 Milan Milan 20146",
            "sex" => Sex::MALE->value,
        ], $participant->driver);

        $this->assertNull($participant->competitor);

        $this->assertEquals('Mechanic Racer', $participant->mechanic['name']);
        $this->assertEquals('M0003', $participant->mechanic['licence_number']);

        $this->assertCount(1, $participant->vehicles);

        $this->assertEquals('Chassis', $participant->vehicles[0]['chassis_manufacturer']);
        $this->assertEquals('Engine Manufacturer', $participant->vehicles[0]['engine_manufacturer']);
        $this->assertEquals('Engine Model', $participant->vehicles[0]['engine_model']);
        $this->assertEquals('Oil Manufacturer', $participant->vehicles[0]['oil_manufacturer']);
        $this->assertEquals('Oil Type', $participant->vehicles[0]['oil_type']);
        $this->assertEquals('4', $participant->vehicles[0]['oil_percentage']);

    }

    public function test_participant_cannot_register_using_existing_bib_in_same_race()
    {
        $this->setAvailableCategories();

        $user = User::factory()->racemanager()->create();

        $race = Race::factory()->create();

        $existingParticipant = Participant::factory()->create([
            'bib' => 100,
            'championship_id' => $race->championship_id,
            'race_id' => $race->getKey(),
        ]);

        $response = $this->actingAs($user)
            ->from(route('races.participants.create', $race))
            ->post(route('races.participants.store', $race), [
                'bib' => 100,
                'category' => 'category_key',

                ...$this->generateValidDriver(),
                ...$this->generateValidCompetitor(),
                ...$this->generateValidMechanic(),
                ...$this->generateValidVehicle(),

                'consent_privacy' => true,

                'use_bonus' => false, // TODO handle use of bonus

            ]);

        $response->assertRedirectToRoute('races.participants.create', $race);

        $response->assertSessionHasErrors('bib');

        $this->assertEquals(1, Participant::where('bib', 100)->count());

    }

    public function test_participant_cannot_register_using_existing_bib_in_championship()
    {

        $this->markTestSkipped();

        $this->setAvailableCategories();

        $user = User::factory()->racemanager()->create();

        $championship = Championship::factory()
            ->has(Race::factory()->count(2))
            ->create();

        list($race, $otherRace) = $championship->races;

        $existingParticipant = Participant::factory()->create([
            'bib' => 100,
            'championship_id' => $championship->getKey(),
            'race_id' => $otherRace->getKey(),
        ]);

        $response = $this->actingAs($user)
            ->from(route('races.participants.create', $race))
            ->post(route('races.participants.store', $race), [
                'bib' => 100,
                'category' => 'category_key',

                ...$this->generateValidDriver(),
                ...$this->generateValidCompetitor(),
                ...$this->generateValidMechanic(),
                ...$this->generateValidVehicle(),

                'consent_privacy' => true,

                'use_bonus' => false, // TODO handle use of bonus

            ]);

        $response->assertRedirectToRoute('races.participants.create', $race);

        $response->assertSessionHasErrors('bib');

        $this->assertEquals(1, Participant::where('bib', 100)->count());

    }
}

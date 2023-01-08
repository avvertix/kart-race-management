<?php

namespace Tests\Feature;

use App\Models\Competitor;
use App\Models\CompetitorLicence;
use App\Models\Driver;
use App\Models\DriverLicence;
use App\Models\Mechanic;
use App\Models\Participant;
use App\Models\Race;
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
            'driver_name' => 'John',
            'driver_surname' => 'Racer',
            'driver_licence_number' => 'D0001',
            'driver_licence_type' => DriverLicence::LOCAL_CLUB->value,
            'driver_licence_renewed_at' => null,
            'driver_nationality' => 'Italy',
            'driver_email' => 'john@racer.local',
            'driver_phone' => '555555555',
            'driver_birth_date' => '1999-11-11',
            'driver_birth_place' => 'Milan',
            'driver_medical_certificate_expiration_date' => today()->addYear()->toDateString(),
            'driver_residence' => [
                'address' => 'via dei Platani, 40',
                'city' => 'Milan',
                'province' => 'Milan',
                'postal_code' => '20146',
            ],
        ];
    }

    protected function generateValidCompetitor()
    {
        return [
            'competitor_name' => 'Parent',
            'competitor_surname' => 'Racer',
            'competitor_licence_number' => 'C0002',
            'competitor_licence_type' => CompetitorLicence::LOCAL->value,
            'competitor_licence_renewed_at' => null,
            'competitor_nationality' => 'Italy',
            'competitor_email' => 'parent@racer.local',
            'competitor_phone' => '54444444',
            'competitor_birth_date' => '1979-11-11',
            'competitor_birth_place' => 'Milan',
            'competitor_residence' => [
                'address' => 'via dei Platani, 40',
                'city' => 'Milan',
                'province' => 'Milan',
                'postal_code' => '20146',
            ],
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
            'vehicles' => [
                [
                    'chassis_manufacturer' => 'Chassis',
                    'engine_manufacturer' => 'Engine Manufacturer',
                    'engine_model' => 'Engine Model',
                    'oil_manufacturer' => 'Oil Manufacturer',
                    'oil_type' => 'Oil Type',
                    'oil_percentage' => '4',
                ],
            ],
        ];
    }

    public function test_participant_can_be_registered()
    {
        $this->setAvailableCategories();

        $user = User::factory()->racemanager()->create();

        $race = Race::factory()->create();

        $driver = Driver::factory()
            ->for($race->championship)
            ->create([
                'bib' => 100,
                'first_name' => 'John',
                'last_name' => 'Racer',
            ]);

        $competitor = Competitor::factory()
            ->for($race->championship)
            ->create();

        $response = $this->actingAs($user)
            ->from(route('races.participants.create', $race))
            ->post(route('races.participants.store', $race), [
                'bib' => 100,
                'category' => 'category_key',

                'licence_type' => DriverLicence::LOCAL_CLUB->value,

                'first_name' => 'John',
                'last_name' => 'Racer',

                'driver' => $driver->getKey(),
                'competitor' => $competitor->getKey(),

                ...$this->generateValidMechanic(),
                ...$this->generateValidVehicle(),

                'consent_privacy' => true,

                'use_bonus' => false, // TODO handle use of bonus

            ]);

        $response->assertRedirectToRoute('races.participants.index', $race);

        $response->assertSessionHasNoErrors();

        $response->assertSessionHas('message', '100 John Racer added.');

        $participant = Participant::with([
                'driver',
                'competitor',
            ])
            ->first();

        $this->assertInstanceOf(Participant::class, $participant);
        
        $this->assertEquals($driver->bib, $participant->bib);
        $this->assertEquals('category_key', $participant->category);
        $this->assertEquals('John', $participant->first_name);
        $this->assertEquals('Racer', $participant->last_name);

        $this->assertInstanceOf(Driver::class, $participant->driver);
        $this->assertTrue($participant->driver->is($driver));
        
        $this->assertInstanceOf(Competitor::class, $participant->competitor);
        $this->assertTrue($participant->competitor->is($competitor));

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

    public function test_participant_cannot_register_using_existing_bib()
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
                'category' => 'mini',

                'driver' => $existingParticipant->driver->getKey(),
                'competitor' => $existingParticipant->competitor->getKey(),
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

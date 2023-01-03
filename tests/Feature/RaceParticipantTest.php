<?php

namespace Tests\Feature;

use App\Models\Competitor;
use App\Models\Driver;
use App\Models\LicenceType;
use App\Models\Mechanic;
use App\Models\Participant;
use App\Models\Race;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RaceParticipantTest extends TestCase
{
    use RefreshDatabase;

    protected function generateValidDriver()
    {
        return [
            'driver_name' => 'John',
            'driver_surname' => 'Racer',
            'driver_licence_number' => 'D0001',
            'driver_licence_type' => LicenceType::LOCAL_CLUB->value,
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
            'competitor_licence_type' => LicenceType::COMPETITOR->value,
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
            'mechanic_name' => 'Mechanic',
            'mechanic_surname' => 'Racer',
            'mechanic_licence_number' => 'M0003',
            'mechanic_licence_type' => LicenceType::MECHANIC->value,
            'mechanic_nationality' => 'Italy',
        ];
    }

    protected function generateValidVehicle()
    {
        return [

        ];
    }

    public function test_participant_can_be_registered()
    {
        $user = User::factory()->racemanager()->create();

        $race = Race::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('races.participants.create', $race))
            ->post(route('races.participants.store', $race), [
                'bib' => 100,
                'category' => 'mini',

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

        $participant = Participant::with([
                'driver',
                'competitor',
                'vehicle',
            ])
            ->first();

        $this->assertInstanceOf(Participant::class, $participant);
        
        $this->assertEquals(100, $participant->bib);
        $this->assertEquals('mini', $participant->category);
        $this->assertEquals('John', $participant->name);
        $this->assertEquals('Racer', $participant->surname);

        $this->assertInstanceOf(Driver::class, $participant->driver);

        $this->assertEquals('John', $participant->driver->name);
        $this->assertEquals('Racer', $participant->driver->surname);
        $this->assertEquals('D0001', $participant->driver->licence_number);
        $this->assertEquals(LicenceType::LOCAL_CLUB, $participant->driver->licence_type);
        $this->assertEquals(null, $participant->driver->licence_renewed_at);
        $this->assertEquals('Italy', $participant->driver->nationality);
        $this->assertEquals('john@racer.local', $participant->driver->email);
        $this->assertEquals('555555555', $participant->driver->phone);
        $this->assertEquals('1999-11-11', $participant->driver->birth_date);
        $this->assertEquals('Milan', $participant->driver->birth_place);
        $this->assertEquals(today()->addYear()->toDateString(), $participant->driver->medical_certificate_expiration_date);
        $this->assertEquals('via dei Platani, 40 Milan Milan 20146', $participant->driver->residence_address);

        $this->assertInstanceOf(Competitor::class, $participant->competitor);

        $this->assertEquals('Parent', $participant->competitor->name);
        $this->assertEquals('Racer', $participant->competitor->surname);
        $this->assertEquals('C0002', $participant->competitor->licence_number);
        $this->assertEquals(LicenceType::COMPETITOR, $participant->competitor->licence_type);
        $this->assertEquals(null, $participant->competitor->licence_renewed_at);
        $this->assertEquals('Italy', $participant->competitor->nationality);
        $this->assertEquals('parent@racer.local', $participant->competitor->email);
        $this->assertEquals('54444444', $participant->competitor->phone);
        $this->assertEquals('1979-11-11', $participant->competitor->birth_date);
        $this->assertEquals('Milan', $participant->competitor->birth_place);
        $this->assertEquals('via dei Platani, 40 Milan Milan 20146', $participant->competitor->residence_address);

        $this->assertInstanceOf(Mechanic::class, $participant->mechanic);

        $this->assertEquals('Mechanic', $participant->mechanic->name);
        $this->assertEquals('Racer', $participant->mechanic->surname);
        $this->assertEquals('M0003', $participant->mechanic->licence_number);
        $this->assertEquals(LicenceType::MECHANIC, $participant->mechanic->licence_type);
        $this->assertEquals('Italy', $participant->mechanic->nationality);
    }

    public function test_participant_cannot_register_using_existing_bib()
    {
        $user = User::factory()->racemanager()->create();

        $race = Race::factory()->create();

        Participant::factory()->create([
            'bib' => 100,
            'championship_id' => $race->championship_id,
            'race_id' => $race->getKey(),
        ]);

        $response = $this->actingAs($user)
            ->from(route('races.participants.create', $race))
            ->post(route('races.participants.store', $race), [
                'bib' => 100,
                'category' => 'mini',

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

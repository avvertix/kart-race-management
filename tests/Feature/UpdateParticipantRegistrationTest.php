<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\UpdateParticipantRegistration;
use App\Models\BibReservation;
use App\Models\Category;
use App\Models\Participant;
use App\Models\Race;
use App\Notifications\ConfirmParticipantRegistration;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\CreateCompetitor;
use Tests\CreateDriver;
use Tests\CreateMechanic;
use Tests\CreateVehicle;
use Tests\TestCase;

class UpdateParticipantRegistrationTest extends TestCase
{
    use CreateCompetitor;
    use CreateDriver;
    use CreateMechanic;
    use CreateVehicle;
    use FastRefreshDatabase;

    public function test_participant_updated_using_complete_form()
    {
        config(['races.registration.form' => 'complete']);

        Notification::fake();

        $race = Race::factory()->create();

        $category = Category::factory()->recycle($race->championship)->create();

        $existingParticipant = Participant::factory()->category($category)->create([
            'bib' => 100,
            'championship_id' => $race->championship->getKey(),
            'race_id' => $race->getKey(),
            'driver_licence' => hash('sha512', 'D0001'),
            'driver' => [
                'licence_number' => 'D0001',
            ],
            'locale' => 'en',
            'use_bonus' => false,
        ]);

        $updateParticipant = app()->make(UpdateParticipantRegistration::class);

        $participant = $updateParticipant($race, $existingParticipant, [
            'bib' => 100,
            'category' => $category->ulid,
            ...$this->generateValidDriver(),
            ...$this->generateValidCompetitor(),
            ...$this->generateValidMechanic(),
            ...$this->generateValidVehicle(),
        ]);

        $this->assertInstanceOf(Participant::class, $participant);
        $this->assertEquals(100, $participant->bib);
        $this->assertEquals($category->ulid, $participant->category);
        $this->assertTrue($participant->racingCategory->is($category));
        $this->assertEquals('John', $participant->first_name);
        $this->assertEquals('Racer', $participant->last_name);
        $this->assertEquals('en', $participant->locale);
        $this->assertFalse($participant->use_bonus);

        $this->assertEquals([
            'first_name' => 'Parent',
            'last_name' => 'Racer',
            'licence_type' => 10,
            'licence_number' => 'C0002',
            'fiscal_code' => 'CMPT-FC',
            'licence_renewed_at' => null,
            'nationality' => 'Italy',
            'email' => 'parent@racer.local',
            'phone' => '54444444',
            'birth_date' => '1979-11-11',
            'birth_place' => 'Milan',
            'residence_address' => [
                'address' => 'via dei Platani, 40',
                'city' => 'Milan',
                'province' => 'Milan',
                'postal_code' => '20146',
            ],
        ], $participant->competitor);

        $this->assertCount(1, $participant->vehicles);
        $this->assertEquals([
            'chassis_manufacturer' => 'Chassis',
            'chassis_model' => 'chassis model',
            'chassis_homologation' => 'cm12345',
            'chassis_number' => 'cn67890',
            'engine_manufacturer' => 'engine manufacturer',
            'engine_model' => 'engine model',
            'engine_homologation' => 'om12345',
            'engine_number' => 'en67890',
            'oil_manufacturer' => 'Oil Manufacturer',
            'oil_type' => 'Oil Type',
            'oil_percentage' => '4',
        ], $participant->vehicles[0]);

        $this->assertEquals([
            'name' => 'Mechanic Racer',
            'licence_number' => 'M0003',
        ], $participant->mechanic);

        Notification::assertCount(2);

        Notification::assertSentTo($participant, function (ConfirmParticipantRegistration $notification, $channels) {
            return $notification->target === 'driver';
        });

        Notification::assertSentTo($participant, function (ConfirmParticipantRegistration $notification, $channels) {
            return $notification->target === 'competitor';
        });
    }

    public function test_participant_updated_using_minimal_form()
    {
        config(['races.registration.form' => 'minimal']);

        Notification::fake();

        $race = Race::factory()->create();

        $category = Category::factory()->recycle($race->championship)->create();

        $existingParticipant = Participant::factory()->category($category)->create([
            'bib' => 100,
            'championship_id' => $race->championship->getKey(),
            'race_id' => $race->getKey(),
            'driver_licence' => hash('sha512', 'D0001'),
            'driver' => [
                'licence_number' => 'D0001',
            ],
            'locale' => 'en',
            'use_bonus' => false,
        ]);

        $updateParticipant = app()->make(UpdateParticipantRegistration::class);

        $participant = $updateParticipant($race, $existingParticipant, [
            'bib' => 100,
            'category' => $category->ulid,
            ...$this->generateValidDriver(['driver_licence_type', 'driver_sex', 'driver_medical_certificate_expiration_date']),
            ...$this->generateValidCompetitor(['competitor_licence_type']),
            'consent_privacy' => true,
        ]);

        $this->assertInstanceOf(Participant::class, $participant);
        $this->assertEquals(100, $participant->bib);
        $this->assertEquals($category->ulid, $participant->category);
        $this->assertTrue($participant->racingCategory->is($category));
        $this->assertEquals('John', $participant->first_name);
        $this->assertEquals('Racer', $participant->last_name);
        $this->assertEquals('en', $participant->locale);
        $this->assertFalse($participant->use_bonus);

        $this->assertEquals([
            'first_name' => 'John',
            'last_name' => 'Racer',
            'licence_type' => 10,
            'licence_number' => 'D0001',
            'fiscal_code' => 'DRV-FC',
            'licence_renewed_at' => null,
            'nationality' => 'Italy',
            'email' => 'john@racer.local',
            'phone' => '555555555',
            'birth_date' => '1999-11-11',
            'birth_place' => 'Milan',
            'medical_certificate_expiration_date' => null,
            'residence_address' => [
                'address' => 'via dei Platani, 40',
                'city' => 'Milan',
                'province' => 'Milan',
                'postal_code' => '20146',
            ],
            'sex' => 30,
        ], $participant->driver);

        $this->assertEquals([
            'first_name' => 'Parent',
            'last_name' => 'Racer',
            'licence_type' => 10,
            'licence_number' => 'C0002',
            'fiscal_code' => 'CMPT-FC',
            'licence_renewed_at' => null,
            'nationality' => 'Italy',
            'email' => 'parent@racer.local',
            'phone' => '54444444',
            'birth_date' => '1979-11-11',
            'birth_place' => 'Milan',
            'residence_address' => [
                'address' => 'via dei Platani, 40',
                'city' => 'Milan',
                'province' => 'Milan',
                'postal_code' => '20146',
            ],
        ], $participant->competitor);

        $this->assertCount(0, $participant->vehicles);

        $this->assertEmpty($participant->mechanic);
    }

    public function test_participant_not_updated_when_input_and_reservation_does_not_match()
    {
        Notification::fake();

        $race = Race::factory()->create();

        $category = Category::factory()->recycle($race->championship)->create();

        $reservation = BibReservation::factory()
            ->recycle($race->championship)
            ->withLicence()
            ->create([
                'bib' => '100',
                'driver' => 'John Racer',
                'driver_licence' => 'D0001',
                'driver_licence_hash' => hash('sha512', 'D0001'),
            ]);

        $existingParticipant = Participant::factory()->category($category)->create([
            'bib' => 100,
            'championship_id' => $race->championship->getKey(),
            'race_id' => $race->getKey(),
            'driver_licence' => hash('sha512', 'D0001'),
            'driver' => [
                'licence_number' => 'D0001',
            ],
            'locale' => 'en',
            'use_bonus' => false,
        ]);

        $updateParticipant = app()->make(UpdateParticipantRegistration::class);

        try {
            $participant = $updateParticipant($race, $existingParticipant, [
                'bib' => 101,
                'category' => $category->ulid,
                ...$this->generateValidDriver(),
                ...$this->generateValidVehicle(),
                'consent_privacy' => true,
            ]);

            $this->travelBack();

            $this->fail('Expected ValidationException. Nothing thrown.');

        } catch (ValidationException $th) {

            $this->travelBack();

            $this->assertEquals([
                'bib' => [
                    'The entered bib (101) does not reflect what has been reserved (100) to the driven with the given licence.',
                ],
            ], $th->errors());
        }

    }

    public function test_cost_updated_when_category_changed_to_different_registration_price()
    {
        config([
            'races.price' => 15000,
            'races.registration.form' => 'complete',
        ]);

        Notification::fake();

        $race = Race::factory()->create();

        $categoryA = Category::factory()
            ->recycle($race->championship)
            ->create([
                'name' => 'Category A',
                'registration_price' => 10000,
            ]);

        $categoryB = Category::factory()
            ->recycle($race->championship)
            ->create([
                'name' => 'Category B',
                'registration_price' => 20000,
            ]);

        $existingParticipant = Participant::factory()->category($categoryA)->create([
            'bib' => 100,
            'championship_id' => $race->championship->getKey(),
            'race_id' => $race->getKey(),
            'driver_licence' => hash('sha512', 'D0001'),
            'driver' => [
                'licence_number' => 'D0001',
            ],
            'locale' => 'en',
            'use_bonus' => false,
            'cost' => new \App\Data\RegistrationCostData(
                registration_cost: 10000,
                tire_cost: 0,
                tire_model: null,
                discount: 0,
            ),
        ]);

        $this->assertEquals(10000, $existingParticipant->cost->registration_cost);

        $updateParticipant = app()->make(UpdateParticipantRegistration::class);

        $participant = $updateParticipant($race, $existingParticipant, [
            'bib' => 100,
            'category' => $categoryB->ulid,
            ...$this->generateValidDriver(),
            ...$this->generateValidCompetitor(),
            ...$this->generateValidMechanic(),
            ...$this->generateValidVehicle(),
        ]);

        $participant->refresh();

        // Cost should be updated to match the new category's registration price
        $this->assertEquals(20000, $participant->cost->registration_cost);
    }
}

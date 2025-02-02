<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\RegisterParticipant;
use App\Models\BibReservation;
use App\Models\Bonus;
use App\Models\Category;
use App\Models\Participant;
use App\Models\Race;
use App\Notifications\ConfirmParticipantRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\CreateCompetitor;
use Tests\CreateDriver;
use Tests\CreateMechanic;
use Tests\CreateVehicle;
use Tests\TestCase;

class RegisterParticipantTest extends TestCase
{
    use CreateCompetitor;
    use CreateDriver;
    use CreateMechanic;
    use CreateVehicle;
    use RefreshDatabase;

    public function test_participant_registered_using_complete_form()
    {
        config(['races.registration.form' => 'complete']);

        Notification::fake();

        $race = Race::factory()->create();

        $category = Category::factory()->recycle($race->championship)->create();

        $this->travelTo($race->registration_closes_at->subHour());

        $registerParticipant = app()->make(RegisterParticipant::class);

        $participant = $registerParticipant($race, [
            'bib' => 100,
            'category' => $category->ulid,
            ...$this->generateValidDriver(),
            ...$this->generateValidCompetitor(),
            ...$this->generateValidMechanic(),
            ...$this->generateValidVehicle(),
            'consent_privacy' => true,
        ]);

        $this->travelBack();

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
            'engine_manufacturer' => 'engine manufacturer',
            'engine_model' => 'engine model',
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

    public function test_participant_registered_using_minimal_form()
    {
        config(['races.registration.form' => 'minimal']);

        Notification::fake();

        $race = Race::factory()->create();

        $category = Category::factory()->recycle($race->championship)->create();

        $this->travelTo($race->registration_closes_at->subHour());

        $registerParticipant = app()->make(RegisterParticipant::class);

        $participant = $registerParticipant($race, [
            'bib' => 100,
            'category' => $category->ulid,
            ...$this->generateValidDriver(['driver_licence_type', 'driver_sex', 'driver_medical_certificate_expiration_date']),
            ...$this->generateValidCompetitor(['competitor_licence_type']),
            'consent_privacy' => true,
        ]);

        $this->travelBack();

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

        Notification::assertCount(2);

        Notification::assertSentTo($participant, function (ConfirmParticipantRegistration $notification, $channels) {
            return $notification->target === 'driver';
        });

        Notification::assertSentTo($participant, function (ConfirmParticipantRegistration $notification, $channels) {
            return $notification->target === 'competitor';
        });
    }

    public function test_reservation_verified_when_registering()
    {
        Notification::fake();

        $race = Race::factory()->create();

        $category = Category::factory()->recycle($race->championship)->create();

        $reservation = BibReservation::factory()
            ->withLicence()
            ->create([
                'bib' => '100',
                'driver' => 'John Racer',
                'driver_licence' => 'D0001',
                'driver_licence_hash' => hash('sha512', 'D0001'),
            ]);

        $this->travelTo($race->registration_closes_at->subHour());

        $registerParticipant = app()->make(RegisterParticipant::class);

        $participant = $registerParticipant($race, [
            'bib' => 100,
            'category' => $category->ulid,
            ...$this->generateValidDriver(),
            ...$this->generateValidVehicle(),
            'consent_privacy' => true,
        ]);

        $this->travelBack();

        $this->assertInstanceOf(Participant::class, $participant);
        $this->assertEquals(100, $participant->bib);
        $this->assertEquals($category->ulid, $participant->category);
        $this->assertTrue($participant->racingCategory->is($category));
        $this->assertEquals('John', $participant->first_name);
        $this->assertEquals('Racer', $participant->last_name);
        $this->assertEquals('en', $participant->locale);
    }

    public function test_reservation_verified_using_full_name_when_registering()
    {
        Notification::fake();

        $race = Race::factory()->create();

        $category = Category::factory()->recycle($race->championship)->create();

        $reservation = BibReservation::factory()
            ->create([
                'bib' => '100',
                'driver' => 'John Racer',
            ]);

        $this->travelTo($race->registration_closes_at->subHour());

        $registerParticipant = app()->make(RegisterParticipant::class);

        $participant = $registerParticipant($race, [
            'bib' => 100,
            'category' => $category->ulid,
            ...$this->generateValidDriver(),
            ...$this->generateValidVehicle(),
            'consent_privacy' => true,
        ]);

        $this->travelBack();

        $this->assertInstanceOf(Participant::class, $participant);
        $this->assertEquals(100, $participant->bib);
        $this->assertEquals($category->ulid, $participant->category);
        $this->assertTrue($participant->racingCategory->is($category));
        $this->assertEquals('John', $participant->first_name);
        $this->assertEquals('Racer', $participant->last_name);
        $this->assertEquals('en', $participant->locale);
    }

    public function test_reservation_ignored_if_expired()
    {
        Notification::fake();

        $race = Race::factory()->create();

        $category = Category::factory()->recycle($race->championship)->create();

        $reservation = BibReservation::factory()
            ->withLicence()
            ->expired()
            ->create([
                'bib' => '100',
                'driver' => 'John Racer',
                'driver_licence' => 'D0002',
                'driver_licence_hash' => hash('sha512', 'D0002'),
            ]);

        $this->travelTo($race->registration_closes_at->subHour());

        $registerParticipant = app()->make(RegisterParticipant::class);

        $participant = $registerParticipant($race, [
            'bib' => 100,
            'category' => $category->ulid,
            ...$this->generateValidDriver(),
            ...$this->generateValidVehicle(),
            'consent_privacy' => true,
        ]);

        $this->travelBack();

        $this->assertInstanceOf(Participant::class, $participant);
        $this->assertEquals(100, $participant->bib);
        $this->assertEquals($category->ulid, $participant->category);
        $this->assertTrue($participant->racingCategory->is($category));
        $this->assertEquals('John', $participant->first_name);
        $this->assertEquals('Racer', $participant->last_name);
        $this->assertEquals('en', $participant->locale);
    }

    public function test_participant_cannot_register_with_reserved_number_when_licence_does_not_match()
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
                'driver_licence' => 'D0002',
                'driver_licence_hash' => hash('sha512', 'D0002'),
            ]);

        $this->travelTo($race->registration_closes_at->subHour());

        $registerParticipant = app()->make(RegisterParticipant::class);

        try {
            $registerParticipant($race, [
                'bib' => 100,
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
                    'The entered bib is already reserved to another driver. Please check your licence number or contact the support.',
                ],
            ], $th->errors());
        }

    }

    public function test_participant_cannot_register_with_reserved_number_when_name_ambiguous()
    {
        // This covers an edge case when the organized doesn't know the licence number
        // when adding a reservation. The registration is denied if driver name is
        // not exactly equal to what is inserted in the reservation
        Notification::fake();

        $race = Race::factory()->create();

        $category = Category::factory()->recycle($race->championship)->create();

        $reservation = BibReservation::factory()
            ->recycle($race->championship)
            ->create([
                'bib' => '100',
                'driver' => 'John Herby Racer',
            ]);

        $this->travelTo($race->registration_closes_at->subHour());

        $registerParticipant = app()->make(RegisterParticipant::class);

        try {
            $registerParticipant($race, [
                'bib' => 100,
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
                    'The entered bib might be reserved to another driver. Please contact the organizer.',
                ],
            ], $th->errors());
        }

    }

    public function test_participant_cannot_register_when_input_and_reservation_does_not_match()
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

        $this->travelTo($race->registration_closes_at->subHour());

        $registerParticipant = app()->make(RegisterParticipant::class);

        try {
            $registerParticipant($race, [
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
                    'The entered bib does not reflect what has been reserved to the driven with the given licence.',
                ],
            ], $th->errors());
        }

    }

    public function test_participant_use_bonus()
    {
        Notification::fake();

        $race = Race::factory()->create();

        $category = Category::factory()->recycle($race->championship)->create();

        $bonus = Bonus::factory()->recycle($race->championship)->create([
            'driver_licence' => 'D0001',
            'driver_licence_hash' => hash('sha512', 'D0001'),
            'amount' => 1,
        ]);

        $this->travelTo($race->registration_closes_at->subHour());

        $registerParticipant = app()->make(RegisterParticipant::class);

        $participant = $registerParticipant($race, [
            'bib' => 100,
            'category' => $category->ulid,
            ...$this->generateValidDriver(),
            ...$this->generateValidCompetitor(),
            ...$this->generateValidMechanic(),
            ...$this->generateValidVehicle(),
            'consent_privacy' => true,
        ]);

        $this->travelBack();

        $this->assertInstanceOf(Participant::class, $participant);
        $this->assertEquals(100, $participant->bib);
        $this->assertEquals($category->ulid, $participant->category);
        $this->assertTrue($participant->racingCategory->is($category));
        $this->assertEquals('John', $participant->first_name);
        $this->assertEquals('Racer', $participant->last_name);
        $this->assertEquals('en', $participant->locale);
        $this->assertTrue($participant->use_bonus);

        $this->assertTrue($participant->bonuses()->first()->is($bonus));

        $updatedBonus = $bonus->fresh();

        $this->assertEquals(0, $updatedBonus->remaining());
        $this->assertFalse($updatedBonus->hasRemaining());

        Notification::assertCount(2);

        Notification::assertSentTo($participant, function (ConfirmParticipantRegistration $notification, $channels) {
            return $notification->target === 'driver';
        });

        Notification::assertSentTo($participant, function (ConfirmParticipantRegistration $notification, $channels) {
            return $notification->target === 'competitor';
        });
    }

    public function test_participant_cannot_use_bonus_when_not_remaining_ones()
    {
        Notification::fake();

        $race = Race::factory()->create();

        $otherRace = Race::factory()->recycle($race->championship)->create();

        $category = Category::factory()->recycle($race->championship)->create();

        $participationToFirstRace = Participant::factory()
            ->for($otherRace)
            ->for($race->championship)
            ->category($category)
            ->driver([
                'first_name' => 'John',
                'last_name' => 'Racer',
                'licence_number' => 'D0001',
                'bib' => 100,
            ])
            ->create();

        $bonus = Bonus::factory()
            ->recycle($race->championship)

            ->create([
                'driver_licence' => 'D0001',
                'driver_licence_hash' => hash('sha512', 'D0001'),
                'amount' => 1,
            ]);

        $bonus->usages()->attach($participationToFirstRace);

        $this->travelTo($race->registration_closes_at->subHour());

        $registerParticipant = app()->make(RegisterParticipant::class);

        $participant = $registerParticipant($race, [
            'bib' => 100,
            'category' => $category->ulid,
            ...$this->generateValidDriver(),
            ...$this->generateValidCompetitor(),
            ...$this->generateValidMechanic(),
            ...$this->generateValidVehicle(),
            'consent_privacy' => true,
        ]);

        $this->travelBack();

        $this->assertInstanceOf(Participant::class, $participant);
        $this->assertEquals(100, $participant->bib);
        $this->assertEquals($category->ulid, $participant->category);
        $this->assertTrue($participant->racingCategory->is($category));
        $this->assertEquals('John', $participant->first_name);
        $this->assertEquals('Racer', $participant->last_name);
        $this->assertEquals('en', $participant->locale);
        $this->assertFalse($participant->use_bonus);

        Notification::assertCount(2);

        Notification::assertSentTo($participant, function (ConfirmParticipantRegistration $notification, $channels) {
            return $notification->target === 'driver';
        });

        Notification::assertSentTo($participant, function (ConfirmParticipantRegistration $notification, $channels) {
            return $notification->target === 'competitor';
        });
    }


    public function test_participant_cannot_register_to_cancelled_race()
    {
        Notification::fake();

        $race = Race::factory()->cancelled()->create();

        $category = Category::factory()->recycle($race->championship)->create();

        $registerParticipant = app()->make(RegisterParticipant::class);

        try{

            $participant = $registerParticipant($race, [
                'bib' => 100,
                'category' => $category->ulid,
                ...$this->generateValidDriver(),
                ...$this->generateValidCompetitor(),
                ...$this->generateValidMechanic(),
                ...$this->generateValidVehicle(),
                'consent_privacy' => true,
            ]);

            $this->fail('Expected ValidationException. Nothing thrown.');

        } catch (ValidationException $th) {

            $this->assertEquals([
                'bib' => [
                    'The race has been cancelled and registration is now closed.',
                ],
            ], $th->errors());
        }

        Notification::assertCount(0);
    }
}

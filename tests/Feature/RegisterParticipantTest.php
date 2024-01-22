<?php

namespace Tests\Feature;

use App\Actions\RegisterParticipant;
use App\Models\BibReservation;
use App\Models\Category;
use App\Models\Championship;
use App\Models\Participant;
use App\Models\Race;
use App\Notifications\ConfirmParticipantRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Tests\CreateCompetitor;
use Tests\CreateDriver;
use Tests\CreateMechanic;
use Tests\CreateVehicle;
use Tests\TestCase;

class RegisterParticipantTest extends TestCase
{
    use RefreshDatabase;
    use CreateDriver;
    use CreateCompetitor;
    use CreateMechanic;
    use CreateVehicle;   

    
    public function test_participant_registered()
    {
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
            'use_bonus' => 'false',
        ]);

        $this->travelBack();

        $this->assertInstanceOf(Participant::class, $participant);
        $this->assertEquals(100, $participant->bib);
        $this->assertEquals($category->ulid, $participant->category);
        $this->assertTrue($participant->racingCategory->is($category));
        $this->assertEquals('John', $participant->first_name);
        $this->assertEquals('Racer', $participant->last_name);
        $this->assertEquals('en', $participant->locale);

        Notification::assertCount(2);

        Notification::assertSentTo($participant, function(ConfirmParticipantRegistration $notification, $channels){
            return $notification->target === 'driver';
        });

        Notification::assertSentTo($participant, function(ConfirmParticipantRegistration $notification, $channels){
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
                'bib' => "100",
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
            'use_bonus' => 'false',
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
                'bib' => "100",
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
            'use_bonus' => 'false',
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
                'bib' => "100",
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
                'use_bonus' => 'false',
            ]);

            $this->travelBack();

            $this->fail('Expected ValidationException. Nothing thrown.');

        } catch (ValidationException $th) {

            $this->travelBack();
            
            $this->assertEquals([
                    'bib' => [
                        "The entered bib is already reserved to another driver. Please check your licence number or contact the support."
                    ]
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
                'bib' => "100",
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
                'use_bonus' => 'false',
            ]);

            $this->travelBack();

            $this->fail('Expected ValidationException. Nothing thrown.');

        } catch (ValidationException $th) {

            $this->travelBack();
            
            $this->assertEquals([
                    'bib' => [
                        'The entered bib does not reflect what has been reserved to the driven with the given licence.'
                    ]
                ], $th->errors());
        }

    }

    
    
}

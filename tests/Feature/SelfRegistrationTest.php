<?php

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\Race;
use App\Notifications\ConfirmParticipantRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\CreateCompetitor;
use Tests\CreateDriver;
use Tests\CreateMechanic;
use Tests\CreateVehicle;
use Tests\TestCase;

class SelfRegistrationTest extends TestCase
{
    use RefreshDatabase;
    use CreateDriver;
    use CreateCompetitor;
    use CreateMechanic;
    use CreateVehicle;

    protected function setAvailableCategories()
    {
        config([
            'categories.default' => [
                'category_key' => [
                    'name' => 'CAT 1',
                    'tires' => 'T1',
                ],
            ],
            'races.tires' => [
                'T1' => [
                    'name' => 'T1',
                    'price' => 10,
                ],
            ],
        ]);
    }

    

    public function test_participant_can_self_register()
    {
        Notification::fake();
        
        $this->setAvailableCategories();

        $race = Race::factory()->create();

        $response = $this
            ->from(route('races.registration.create', $race))
            ->post(route('races.registration.store', $race), [
                'bib' => 100,
                'category' => 'category_key',
                ...$this->generateValidDriver(),
                ...$this->generateValidCompetitor(),
                ...$this->generateValidMechanic(),
                ...$this->generateValidVehicle(),
                'consent_privacy' => true,
                'use_bonus' => false,
            ]);

        $participant = Participant::first();

        $this->assertInstanceOf(Participant::class, $participant);
        $this->assertEquals(100, $participant->bib);
        $this->assertEquals('category_key', $participant->category);
        $this->assertEquals('John', $participant->first_name);
        $this->assertEquals('Racer', $participant->last_name);

        $response->assertRedirectToSignedRoute('registration.show', [
            'registration' => $participant,
            'p' => $participant->signatureContent()
        ]);

        $response->assertSessionHasNoErrors();

        $response->assertSessionHas('flash.banner', 'Race registration recorded. Please confirm it using the link sent in the email.');

        Notification::assertCount(2);

        Notification::assertSentTo($participant, function(ConfirmParticipantRegistration $notification, $channels){
            return $notification->target === 'driver';
        });

        Notification::assertSentTo($participant, function(ConfirmParticipantRegistration $notification, $channels){
            return $notification->target === 'competitor';
        });
    }

    public function test_participant_can_access_registration_receipt()
    {
        $this->setAvailableCategories();

        $race = Race::factory()->create();

        $participant = Participant::factory()->for($race)->create();

        $response = $this
            ->get(URL::signedRoute('registration.show', ['registration' => $participant, 'p' => $participant->signatureContent()]));

        $response->assertOk();

        $response->assertViewHas('participant', $participant);
        $response->assertViewHas('race', $participant->race);
        $response->assertViewHas('championship', $participant->championship);

        $response->assertSeeTextInOrder([
            $participant->bib,
            $participant->first_name,
            $participant->last_name,
        ]);
    }

    public function test_participant_cannot_access_registration_receipt_with_invalid_signature()
    {

        $race = Race::factory()->create();

        $participant = Participant::factory()->for($race)->create();

        $response = $this
            ->get(route('registration.show', $participant));

        $response->assertUnauthorized();
    }
}

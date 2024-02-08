<?php

namespace Tests\Feature;

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

    public function test_registration_form_loads()
    {
        $race = Race::factory()->create();

        $category = Category::factory()->recycle($race->championship)->create();

        $this->travelTo($race->registration_closes_at->subHour());

        $response = $this
            ->get(route('races.registration.create', $race));

        $this->travelBack();

        $response->assertOk();

        $response->assertSee("Register for {$race->name}");
        $response->assertSee("Race number and category");
        $response->assertSee("Driver");
        $response->assertSee("Competitor");
        $response->assertSee("Mechanic");
        $response->assertSee("Vehicle");
        $response->assertDontSee("Bonus");
        $response->assertSee("Consents");
        $response->assertSee("Rules");
        $response->assertSee("Participation price");
    }

    public function test_participant_limit_message_visible_on_registration_form()
    {        
        $race = Race::factory()->withTotalParticipantLimit()->create();

        $this->travelTo($race->registration_closes_at->subHour());

        $response = $this
            ->get(route('races.registration.create', $race));

        $this->travelBack();

        $response->assertOk();

        $response->assertSee("Limited number competition");
        $response->assertSee("In this race we can only accept a maximum of 10 participants.");
    }

    public function test_participant_limit_reached_message_visible_on_registration_form()
    {        

        $race = Race::factory()
            ->withTotalParticipantLimit(1)
            ->has(Participant::factory(), 'participants')
            ->create();

        $this->travelTo($race->registration_closes_at->subHour());

        $response = $this
            ->get(route('races.registration.create', $race));

        $this->travelBack();

        $response->assertOk();

        $response->assertSee("We reached the maximum allowed participants to this race.");
    }

    public function test_participant_can_self_register()
    {
        Notification::fake();

        $race = Race::factory()->create();

        $category = Category::factory()->recycle($race->championship)->create();

        $this->travelTo($race->registration_closes_at->subHour());

        $response = $this
            ->from(route('races.registration.create', $race))
            ->post(route('races.registration.store', $race), [
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

        $participant = Participant::first();

        $this->assertInstanceOf(Participant::class, $participant);
        $this->assertEquals(100, $participant->bib);
        $this->assertEquals($category->ulid, $participant->category);
        $this->assertTrue($participant->racingCategory->is($category));
        $this->assertEquals('John', $participant->first_name);
        $this->assertEquals('Racer', $participant->last_name);
        $this->assertEquals('en', $participant->locale);

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

    
    public function test_participant_bib_cannot_be_changed_during_championship()
    {
        Notification::fake();
        
        $championship = Championship::factory()
            ->has(Race::factory()->count(2))
            ->create();

        $category = Category::factory()->recycle($championship)->create();

        $firstRace = $championship->races->first();
        $race = $championship->races->last();
        
        $participationToFirstRace = Participant::factory()
            ->for($firstRace)
            ->for($championship)
            ->category($category)
            ->driver([
                'first_name' => 'John',
                'last_name' => 'Racer',
                'licence_number' => 'D0001',
                'bib' => 80,
            ])
            ->create();

        $this->travelTo($race->registration_closes_at->subHour());

        $response = $this
            ->from(route('races.registration.create', $race))
            ->post(route('races.registration.store', $race), [
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

        
        $response->assertRedirectToRoute('races.registration.create', $race);

        $response->assertSessionHasErrors([
            'bib' => 'The entered bib does not reflect what has been used so far in the championship by the same driver.',
        ]);

        $this->assertEquals(0, $race->participants()->count());

        Notification::assertNothingSent();
    }



    public function test_last_participant_can_self_register()
    {
        Notification::fake();

        $race = Race::factory()
            ->withTotalParticipantLimit(2)
            ->has(Participant::factory()->category(), 'participants')
            ->create();

        $category = Category::factory()->recycle($race->championship)->create();

        $this->travelTo($race->registration_closes_at->subHour());

        $response = $this
            ->from(route('races.registration.create', $race))
            ->post(route('races.registration.store', $race), [
                'bib' => 100,
                'category' => $category->ulid,
                ...$this->generateValidDriver(),
                ...$this->generateValidCompetitor(),
                ...$this->generateValidMechanic(),
                ...$this->generateValidVehicle(),
                'consent_privacy' => true,
                'use_bonus' => 'false',
            ]);

        $response->assertSessionDoesntHaveErrors();
        
        $this->travelBack();

        $participant = Participant::latest()->first();

        $this->assertInstanceOf(Participant::class, $participant);
        $this->assertEquals(100, $participant->bib);
        $this->assertTrue($participant->racingCategory->is($category));
        $this->assertEquals('John', $participant->first_name);
        $this->assertEquals('Racer', $participant->last_name);
        $this->assertEquals('en', $participant->locale);

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
    
    public function test_participant_cannot_register_if_limit_is_reached()
    {
        Notification::fake();

        $race = Race::factory()
            ->withTotalParticipantLimit(1)
            ->has(Participant::factory()->category(), 'participants')
            ->create();

        $category = Category::factory()->recycle($race->championship)->create();

        $this->travelTo($race->registration_closes_at->subHour());

        $response = $this
            ->from(route('races.registration.create', $race))
            ->post(route('races.registration.store', $race), [
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


        $response->assertRedirectToRoute('races.registration.create', $race);

        $response->assertSessionHasErrors([
            'participants_limit' => 'We reached the maximum allowed participants to this race.',
        ]);

        $this->assertEquals(1, $race->participants()->count());

        Notification::assertNothingSent();
    }

    public function test_participant_preferred_language_saved()
    {
        Notification::fake();

        $race = Race::factory()->create();

        $category = Category::factory()->recycle($race->championship)->create();

        $this->travelTo($race->registration_closes_at->subHour());

        $response = $this
            ->withHeader('Accept-Language', 'it;q=90, en;q=10')
            ->from(route('races.registration.create', $race))
            ->post(route('races.registration.store', $race), [
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

        $participant = Participant::first();

        $this->assertInstanceOf(Participant::class, $participant);
        $this->assertEquals(100, $participant->bib);
        $this->assertEquals($category->ulid, $participant->category);
        $this->assertTrue($participant->racingCategory->is($category));
        $this->assertEquals('John', $participant->first_name);
        $this->assertEquals('Racer', $participant->last_name);
        $this->assertEquals('it', $participant->locale);

        $response->assertRedirectToSignedRoute('registration.show', [
            'registration' => $participant,
            'p' => $participant->signatureContent()
        ]);

        $response->assertSessionHasNoErrors();

        $response->assertSessionHas('flash.banner', __('Race registration recorded. Please confirm it using the link sent in the email.', [], 'it'));

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
        $race = Race::factory()->create();

        $participant = Participant::factory()->for($race)->recycle($race->championship)->category()->create();

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

        $response->assertForbidden();

        $response->assertViewIs('errors.participant-link-invalid');
    }
}

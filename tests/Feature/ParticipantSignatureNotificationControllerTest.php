<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\Race;
use App\Models\Signature;
use App\Notifications\ConfirmParticipantRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ParticipantSignatureNotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_participant_can_request_new_verification_link()
    {
        Notification::fake();

        $race = Race::factory()->create();

        $participant = Participant::factory()->for($race)->create();

        $response = $this
            ->from(route('registration.show', $participant))
            ->post(URL::signedRoute('registration-verification.send', $participant->signedUrlParameters()), [
                'participant' => $participant->uuid,
            ]);

        $response->assertRedirectToRoute('registration.show', $participant);

        $response->assertSessionHas('status', 'verification-link-sent');

        Notification::assertSentTo($participant, function (ConfirmParticipantRegistration $notification, $channels) {
            return $notification->target === 'driver';
        });

    }

    public function test_new_verification_link_not_sent_if_participation_already_signed()
    {
        Notification::fake();

        $race = Race::factory()->create();

        $participant = Participant::factory()
            ->for($race)
            ->has(Signature::factory())
            ->create();

        $response = $this
            ->from(route('registration.show', $participant))
            ->post(URL::signedRoute('registration-verification.send', $participant->signedUrlParameters()), [
                'participant' => $participant->uuid,
            ]);

        $response->assertRedirect($participant->qrCodeUrl());

        $response->assertSessionMissing('status');

        Notification::assertNothingSent();

    }

    public function test_new_verification_link_cannot_be_requested_with_wrong_signature()
    {
        Notification::fake();

        $race = Race::factory()->create();

        $participant = Participant::factory()
            ->for($race)
            ->has(Signature::factory())
            ->create();

        $response = $this
            ->from(route('registration.show', $participant))
            ->post(route('registration-verification.send'), [
                'participant' => $participant->uuid,
            ]);

        $response->assertForbidden();

        Notification::assertNothingSent();

    }
}

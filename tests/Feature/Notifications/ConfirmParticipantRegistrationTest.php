<?php

namespace Tests\Feature\Notifications;

use App\Models\Participant;
use App\Notifications\ConfirmParticipantRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ConfirmParticipantRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_includes_messages()
    {

        Carbon::setTestNow();

        $notifiable = Participant::factory()->create();

        $notification = new ConfirmParticipantRegistration();

        $mail = $notification->toMail($notifiable);

        $this->assertEquals("Verify the email and confirm the participation to {$notifiable->race->title}", $mail->subject);

        $rendered = $mail->render();

        $this->assertNotNull($rendered);

        $this->assertStringContainsString("{$notifiable->first_name} {$notifiable->last_name}", $rendered);
        $this->assertStringContainsString($notifiable->race->title, $rendered);
        $this->assertStringContainsString(e($notifiable->qrCodeUrl()), $rendered);
        $this->assertStringContainsString(e(URL::temporarySignedRoute(
            'participant.sign.create',
            Carbon::now()->addMinutes(Config::get('participant.verification.expire', 60)),
            [
                'p' => (string)$notifiable->uuid,
                't' => 'driver',
                'hash' => sha1($notifiable->getEmailForVerification('driver')),
            ]
        )), $rendered);
    }


    public function test_driver_can_sign_the_participation_request()
    {
        /**
         * @var \App\Models\Participant
         */
        $participant = Participant::factory()->create();

        $response = $this->get($participant->verificationUrl('driver'));

        $response->assertRedirect($participant->qrCodeUrl());

        $response->assertSessionHas('message', 'You signed the participation request.');

        $signature = $participant->signatures()->first();

        $this->assertNotNull($signature->signed_at);

        $this->assertEquals(sha1($participant->driver['email']), $signature->signature);
    }

    public function test_competitor_can_sign_the_participation_request()
    {
        /**
         * @var \App\Models\Participant
         */
        $participant = Participant::factory()->withCompetitor()->create();

        $response = $this->get($participant->verificationUrl('competitor'));

        $response->assertRedirect($participant->qrCodeUrl());

        $response->assertSessionHas('message', 'You signed the participation request.');

        $signature = $participant->signatures()->first();

        $this->assertNotNull($signature->signed_at);

        $this->assertEquals(sha1($participant->competitor['email']), $signature->signature);
    }
}

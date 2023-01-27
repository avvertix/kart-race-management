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
                'id' => $notifiable->getKey(),
                'p' => $notifiable->signatureContent(),
                'hash' => sha1($notifiable->getEmailForVerification('driver')),
            ]
        )), $rendered);
    }
}

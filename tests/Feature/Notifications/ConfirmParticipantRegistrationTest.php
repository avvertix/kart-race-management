<?php

namespace Tests\Feature\Notifications;

use App\Models\Participant;
use App\Notifications\ConfirmParticipantRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ConfirmParticipantRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_includes_messages()
    {
        $notifiable = Participant::factory()->create();

        $notification = new ConfirmParticipantRegistration();

        $mail = $notification->toMail($notifiable);

        $rendered = $mail->render();

        $this->assertNotNull($rendered);
    }
}

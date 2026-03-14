<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\Race;
use App\Models\User;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class ConfirmParticipantPaymentControllerTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_authentication_required(): void
    {
        $participant = Participant::factory()->create();

        $response = $this->post(route('participants.confirm-payment', $participant));

        $response->assertRedirectToRoute('login');
    }

    public function test_timekeeper_cannot_confirm_payment(): void
    {
        $user = User::factory()->timekeeper()->create();
        $participant = Participant::factory()->create();

        $response = $this->actingAs($user)->post(route('participants.confirm-payment', $participant));

        $response->assertForbidden();
    }

    public function test_confirms_payment(): void
    {
        $user = User::factory()->organizer()->create();

        $race = Race::factory()->create();

        $participant = Participant::factory()->create([
            'race_id' => $race->getKey(),
            'championship_id' => $race->championship->getKey(),
            'payment_confirmed_at' => null,
        ]);

        $response = $this->actingAs($user)->post(route('participants.confirm-payment', $participant));

        $response->assertRedirect();

        $this->assertNotNull($participant->fresh()->payment_confirmed_at);
    }

    public function test_unconfirms_payment_when_already_confirmed(): void
    {
        $user = User::factory()->organizer()->create();

        $race = Race::factory()->create();

        $participant = Participant::factory()->create([
            'race_id' => $race->getKey(),
            'championship_id' => $race->championship->getKey(),
            'payment_confirmed_at' => now(),
        ]);

        $response = $this->actingAs($user)->post(route('participants.confirm-payment', $participant));

        $response->assertRedirect();

        $this->assertNull($participant->fresh()->payment_confirmed_at);
    }
}

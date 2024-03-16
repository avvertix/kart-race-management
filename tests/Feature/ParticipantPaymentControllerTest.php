<?php

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\Payment;
use App\Models\Race;
use App\Models\Signature;
use App\Notifications\ConfirmParticipantRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ParticipantPaymentControllerTest extends TestCase
{
    use RefreshDatabase;

    
    public function test_participant_can_upload_payment_proof()
    {
        Storage::fake('payments');

        $race = Race::factory()->create();

        $participant = Participant::factory()->for($race)->create();

        $file = UploadedFile::fake()->image('proof.jpg', 200, 200);

        $response = $this
            ->from(route('registration.show', $participant))
            ->post(URL::signedRoute('payment-verification.store', $participant->signedUrlParameters()), [
                'participant' => $participant->uuid,
                'proof' => $file
            ]);

        $response->assertRedirect($participant->qrCodeUrl());

        $response->assertSessionHas('status', 'payment-uploaded');

        Storage::disk('payments')->assertExists($file->hashName());
    }
    
    
    public function test_payment_not_uploaded_with_wrong_signature()
    {
        Storage::fake('payments');

        $race = Race::factory()->create();

        $participant = Participant::factory()
            ->for($race)
            ->create();

        $response = $this
            ->from(route('registration.show', $participant))
            ->post(route('payment-verification.store'), [
                'participant' => $participant->uuid,
            ]);

        $response->assertForbidden();
    }


    public function test_participant_payment_proof_downloadable()
    {
        Storage::fake('payments');

        $race = Race::factory()->create();

        $participant = Participant::factory()
            ->for($race)
            ->has(Payment::factory())
            ->create();


        $file = UploadedFile::fake()->image('proof.jpg', 200, 200);

        Storage::disk('payments')->putFileAs('', $file, 'proof.jpg');

        $payment = $participant->payments->first();

        $response = $this
            ->from(route('registration.show', $participant))
            ->get($payment->download_url);

        $response->assertSuccessful();

        $response->assertHeader('content-type', 'image/jpeg');

        Storage::disk('payments')->assertExists($payment->path);
    }
}

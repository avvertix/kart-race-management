<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\Race;
use App\Models\Tire;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ParticipantTireVerificationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_tires_verification_url_generation()
    {
        $race = Race::factory()->create();

        $participant = Participant::factory()
            ->has(Tire::factory()->count(2)->state([
                'race_id' => $race->getKey(),
            ]))
            ->create([
                'race_id' => $race->getKey(),
                'championship_id' => $race->championship->getKey(),
            ]);

        $url = $participant->tiresQrCodeUrl();

        $this->assertStringContainsString((string) $participant->uuid, $url);
        $this->assertStringContainsString('p='.md5((string) $participant->uuid), $url);
    }

    public function test_tires_listed()
    {
        $user = User::factory()->tireagent()->create();

        $race = Race::factory()->create();

        $participant = Participant::factory()
            ->has(Tire::factory()->count(2)->state([
                'race_id' => $race->getKey(),
            ]))
            ->create([
                'race_id' => $race->getKey(),
                'championship_id' => $race->championship->getKey(),
            ]);

        $response = $this
            ->get($participant->tiresQrCodeUrl());

        $response->assertSuccessful();

        $response->assertViewHas('participant', $participant);

        $response->assertViewHas('race', $participant->race);

        $response->assertViewHas('tires', $participant->tires);
    }

    public function test_participant_signature_verified()
    {
        $race = Race::factory()->create();

        $participant = Participant::factory()
            ->has(Tire::factory()->count(2)->state([
                'race_id' => $race->getKey(),
            ]))
            ->create([
                'race_id' => $race->getKey(),
                'championship_id' => $race->championship->getKey(),
            ]);

        $response = $this
            ->get(URL::signedRoute(
                'tires-verification.show',
                ['registration' => $participant, 'p' => md5('another-value')]
            ));

        $response->assertUnauthorized();

        $response->assertViewIs('errors.participant-tires-link-invalid');
    }
}

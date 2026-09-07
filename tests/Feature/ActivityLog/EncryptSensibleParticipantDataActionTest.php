<?php

declare(strict_types=1);

namespace Tests\Feature\ActivityLog;

use App\Models\Participant;
use Illuminate\Support\Facades\Crypt;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class EncryptSensibleParticipantDataActionTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_driver_and_competitor_emails_are_encrypted_in_the_activity_log(): void
    {
        $participant = Participant::factory()->withCompetitor()->create();

        $originalDriverEmail = $participant->driver['email'];
        $originalCompetitorEmail = $participant->competitor['email'];

        $driver = $participant->driver;
        $driver['email'] = 'new-driver@example.test';
        $participant->driver = $driver;

        $competitor = $participant->competitor;
        $competitor['email'] = 'new-competitor@example.test';
        $participant->competitor = $competitor;

        $participant->save();

        $activity = $participant->activitiesAsSubject()->forEvent('updated')->latest('id')->first();

        $attributes = $activity->attribute_changes->get('attributes');
        $old = $activity->attribute_changes->get('old');

        $this->assertNotSame('new-driver@example.test', $attributes['driver']['email']);
        $this->assertSame('new-driver@example.test', Crypt::decryptString($attributes['driver']['email']));
        $this->assertSame($originalDriverEmail, Crypt::decryptString($old['driver']['email']));

        $this->assertNotSame('new-competitor@example.test', $attributes['competitor']['email']);
        $this->assertSame('new-competitor@example.test', Crypt::decryptString($attributes['competitor']['email']));
        $this->assertSame($originalCompetitorEmail, Crypt::decryptString($old['competitor']['email']));
    }
}

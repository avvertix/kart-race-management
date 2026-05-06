<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Participant;
use App\Notifications\UpdateParticipantRegistration;
use Exception;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class NotifyParticipantUpdateTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_sends_notification_to_participants_by_id_argument(): void
    {
        Notification::fake();

        $participants = Participant::factory()->count(2)->create();

        $this->artisan('participants:notify-update', [
            'participants' => $participants->pluck('id')->all(),
        ])->assertSuccessful();

        Notification::assertSentTo($participants->first(), UpdateParticipantRegistration::class);
        Notification::assertSentTo($participants->last(), UpdateParticipantRegistration::class);
    }

    public function test_sends_notification_to_participants_from_json_file(): void
    {
        Notification::fake();

        $participants = Participant::factory()->count(2)->create();

        Storage::fake()->put('test.json', $participants->pluck('id')->toJson());

        $this->artisan('participants:notify-update', [
            '--file' => 'test.json',
        ])->assertSuccessful();

        Notification::assertSentTo($participants->first(), UpdateParticipantRegistration::class);
        Notification::assertSentTo($participants->last(), UpdateParticipantRegistration::class);

    }

    public function test_merges_id_arguments_and_json_file(): void
    {
        Notification::fake();

        $participants = Participant::factory()->count(3)->create();

        Storage::fake()->put('test.json', json_encode([$participants->get(1)->id, $participants->get(2)->id]));

        $this->artisan('participants:notify-update', [
            'participants' => [$participants->first()->id],
            '--file' => 'test.json',
        ])->assertSuccessful();

        foreach ($participants as $participant) {
            Notification::assertSentTo($participant, UpdateParticipantRegistration::class);
        }

    }

    public function test_deduplicates_ids_before_sending(): void
    {
        Notification::fake();

        $participant = Participant::factory()->create();

        $this->artisan('participants:notify-update', [
            'participants' => [$participant->id, $participant->id],
        ])->assertSuccessful();

        Notification::assertSentToTimes($participant, UpdateParticipantRegistration::class, 1);
    }

    public function test_warns_for_unknown_ids_and_skips_them(): void
    {
        Notification::fake();

        $participant = Participant::factory()->create();

        $this->artisan('participants:notify-update', [
            'participants' => [$participant->id, 'non-existent-id'],
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('non-existent-id');

        Notification::assertSentTo($participant, UpdateParticipantRegistration::class);
        Notification::assertCount(1);
    }

    public function test_fails_when_json_file_does_not_exist(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('File [/non/existent/file.json] not found in storage.');

        $this->artisan('participants:notify-update', [
            '--file' => '/non/existent/file.json',
        ]);
    }

    public function test_fails_when_json_file_is_not_a_valid_array(): void
    {
        Storage::fake()->put('test.json', '"not-an-array"');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('File [test.json] does not contain a valid JSON array.');

        $this->artisan('participants:notify-update', [
            '--file' => 'test.json',
        ])->assertFailed();
    }

    public function test_fails_when_all_ids_are_unknown(): void
    {
        Notification::fake();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No matching participants found.');

        $this->artisan('participants:notify-update', [
            'participants' => ['unknown-id-1', 'unknown-id-2'],
        ]);

        Notification::assertNothingSent();
    }
}

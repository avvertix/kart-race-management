<?php

declare(strict_types=1);

namespace Tests\Feature\Exports;

use App\Exports\PrintRaceReceipts;
use App\Models\CompetitorLicence;
use App\Models\Participant;
use App\Models\Race;
use App\Models\Sex;
use App\Models\User;
use Carbon\Carbon;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;
use Illuminate\Testing\TestResponseAssert as PHPUnit;

class PrintRaceParticipantReceiptsControllerTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_print_requires_authentication()
    {
        $race = Race::factory()->create();

        $response = $this->get(route('races.participant-receipts.print', $race));

        $response->assertRedirect(route('login'));
    }

    public function test_export_forbidden_for_tireagent()
    {
        $user = User::factory()->tireagent()->create();

        $race = Race::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('races.participant-receipts.print', $race));

        $response->assertForbidden();
    }

    public function test_export_forbidden_for_timekeeper()
    {
        $user = User::factory()->timekeeper()->create();

        $race = Race::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('races.participant-receipts.print', $race));

        $response->assertForbidden();
    }

    public function test_export_forbidden_for_racemanager()
    {
        $user = User::factory()->racemanager()->create();

        $race = Race::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('races.participant-receipts.print', $race));

        $response->assertForbidden();
    }

    public function test_print_receipt_return_a_pdf()
    {
        config(['races.organizer.name' => 'Organizer name']);

        $user = User::factory()->organizer()->create();

        $race = Race::factory()
            ->create([
                'event_start_at' => Carbon::parse('2023-02-28'),
                'title' => 'Race title',
            ]);

        $participant = Participant::factory()
            ->recycle($race->championship)
            ->category()
            ->confirmed()
            ->create([
                'race_id' => $race->getKey(),
            ]);

        $response = $this
            ->actingAs($user)
            ->get(route('races.participant-receipts.print', $race));

        $expected_filename = 'receipt-organizer-name-2023-02-28-race-title.pdf';

        $this->assertTrue(str($response->getContent())->substr(0, 4)->is('%PDF'));

        $contentDisposition = explode(';', $response->headers->get('content-disposition', ''));


        if (isset($contentDisposition[1]) &&
            trim(explode('=', $contentDisposition[1])[0]) !== 'filename') {
            PHPUnit::withResponse($response)->fail(
                'Unsupported Content-Disposition header provided.'.PHP_EOL.
                'Disposition ['.trim(explode('=', $contentDisposition[1])[0]).'] found in header, [filename] expected.'
            );
        }

        $message = "Expected file [{$expected_filename}] is not present in Content-Disposition header.";

        if (! isset($contentDisposition[1])) {
            PHPUnit::withResponse($response)->fail($message);
        } else {
            PHPUnit::withResponse($response)->assertSame(
                $expected_filename,
                isset(explode('=', $contentDisposition[1])[1])
                    ? trim(explode('=', $contentDisposition[1])[1], " \"'")
                    : '',
                $message
            );
        }

    }

    public function test_print_receipt_lists_driver_details()
    {
        config(['races.organizer.name' => 'Organizer name']);

        $user = User::factory()->organizer()->create();

        $race = Race::factory()
            ->create([
                'event_start_at' => Carbon::parse('2023-02-28'),
                'title' => 'Race title',
            ]);

        $participant = Participant::factory()
            ->recycle($race->championship)
            ->category()
            ->confirmed()
            ->create([
                'race_id' => $race->getKey(),
            ]);

        $export = new PrintRaceReceipts($race);

        $participantsToPrint = $export->query()->get();
        
        $this->assertCount(1, $participantsToPrint);
        $this->assertTrue($participantsToPrint->first()->is($participant));

    }

    public function test_print_only_confirmed_participants()
    {
        config(['races.organizer.name' => 'Organizer name']);

        $user = User::factory()->organizer()->create();

        $race = Race::factory()
            ->create([
                'event_start_at' => Carbon::parse('2023-02-28'),
                'title' => 'Race title',
            ]);

        $participant = Participant::factory()
            ->recycle($race->championship)
            ->category()
            ->create([
                'race_id' => $race->getKey(),
            ]);

        $export = new PrintRaceReceipts($race);

        $participantsToPrint = $export->query()->get();
        
        $this->assertCount(0, $participantsToPrint);        
    }

    
}

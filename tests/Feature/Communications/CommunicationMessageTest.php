<?php

declare(strict_types=1);

namespace Tests\Feature\Communications;

use App\Models\CommunicationMessage;
use Carbon\Carbon;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class CommunicationMessageTest extends TestCase
{
    use FastRefreshDatabase;

    public static function communication_dates_provider()
    {
        return [
            ['2023-03-31', '2023-04-01', '2023-04-01', 'Scheduled'],
            ['2023-03-31', '2023-03-01', '2023-04-01', 'Active'],
            ['2023-03-31', '2023-03-01', '2023-03-30', 'Expired'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('communication_dates_provider')]
    public function test_communication_status($currentDate, $startsAt, $endsAt, $expectedStatus)
    {
        $this->travelTo(Carbon::parse($currentDate), function () use ($startsAt, $endsAt, $expectedStatus) {
            $communication = CommunicationMessage::factory()->make([
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]);

            $this->assertEquals($expectedStatus, $communication->status);
        });
    }

    public function test_active_messages_can_be_filtered()
    {
        $saved_communications = CommunicationMessage::factory()
            ->count(4)
            ->sequence(
                ['starts_at' => '2023-04-01', 'ends_at' => '2023-04-01'],
                ['starts_at' => '2023-03-01', 'ends_at' => '2023-04-01'],
                ['starts_at' => '2023-03-01', 'ends_at' => '2023-03-30'],
                ['starts_at' => '2023-03-01', 'ends_at' => null],
            )
            ->create();

        $this->travelTo(Carbon::parse('2023-03-31'), function () {
            $communications = CommunicationMessage::query()->active()->get();

            $this->assertEquals(2, $communications->count());
            $this->assertEquals('2023-03-01', $communications[0]->starts_at->toDateString());
            $this->assertEquals('2023-04-01', $communications[0]->ends_at->toDateString());
            $this->assertEquals('2023-03-01', $communications[1]->starts_at->toDateString());
            $this->assertNull($communications[1]->ends_at);
        });
    }
}

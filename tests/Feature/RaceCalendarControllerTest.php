<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\Race;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class RaceCalendarControllerTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_calendar_is_publicly_accessible(): void
    {
        $response = $this->get(route('calendar.races'));

        $response->assertOk();
    }

    public function test_calendar_returns_ical_content_type(): void
    {
        $response = $this->get(route('calendar.races'));

        $response->assertHeader('Content-Type', 'text/calendar; charset=utf-8');
    }

    public function test_calendar_includes_cache_headers(): void
    {
        $response = $this->get(route('calendar.races'));

        $response->assertHeader('Cache-Control', 'max-age=3600, public');
        $response->assertHeader('Expires');
    }

    public function test_calendar_includes_visible_races(): void
    {
        $race = Race::factory()->create([
            'title' => 'Test Race',
            'event_start_at' => now()->addDays(7),
            'event_end_at' => now()->addDays(7)->addHours(8),
            'hide' => false,
            'canceled_at' => null,
        ]);

        $response = $this->get(route('calendar.races'));

        $content = $response->getContent();
        $this->assertStringContainsString('Test Race', $content);
        $this->assertStringContainsString('race-'.$race->uuid, $content);
    }

    public function test_calendar_excludes_invisible_races(): void
    {
        $race = Race::factory()->create([
            'title' => 'Invisible Race',
            'event_start_at' => now()->addDays(7),
            'event_end_at' => now()->addDays(7)->addHours(8),
            'hide' => true,
            'canceled_at' => null,
        ]);

        $response = $this->get(route('calendar.races'));

        $content = $response->getContent();
        $this->assertStringNotContainsString('Invisible Race', $content);
        $this->assertStringNotContainsString('race-'.$race->uuid, $content);
    }

    public function test_calendar_includes_past_races(): void
    {
        Race::factory()->create([
            'title' => 'Past Race',
            'event_start_at' => now()->subDays(7),
            'event_end_at' => now()->subDays(7)->addHours(8),
            'hide' => false,
            'canceled_at' => null,
        ]);

        $response = $this->get(route('calendar.races'));

        $content = $response->getContent();
        $this->assertStringContainsString('Past Race', $content);
    }

    public function test_calendar_excludes_cancelled_races(): void
    {
        Race::factory()->cancelled()->create([
            'title' => 'Cancelled Race',
            'event_start_at' => now()->addDays(7),
            'event_end_at' => now()->addDays(7)->addHours(8),
            'hide' => false,
        ]);

        $response = $this->get(route('calendar.races'));

        $content = $response->getContent();
        $this->assertStringNotContainsString('Cancelled Race', $content);
    }

    public function test_calendar_excludes_hidden_races(): void
    {
        Race::factory()->create([
            'title' => 'Hidden Race',
            'event_start_at' => now()->addDays(7),
            'event_end_at' => now()->addDays(7)->addHours(8),
            'hide' => true,
            'canceled_at' => null,
        ]);

        $response = $this->get(route('calendar.races'));

        $content = $response->getContent();
        $this->assertStringNotContainsString('Hidden Race', $content);
    }

    public function test_calendar_contains_valid_ical_structure(): void
    {
        Race::factory()->create([
            'event_start_at' => now()->addDays(7),
            'event_end_at' => now()->addDays(7)->addHours(8),
            'hide' => false,
        ]);

        $response = $this->get(route('calendar.races'));

        $content = $response->getContent();
        $this->assertStringContainsString('BEGIN:VCALENDAR', $content);
        $this->assertStringContainsString('END:VCALENDAR', $content);
        $this->assertStringContainsString('BEGIN:VEVENT', $content);
        $this->assertStringContainsString('END:VEVENT', $content);
    }

    public function test_calendar_includes_race_details(): void
    {
        $race = Race::factory()->create([
            'title' => 'Championship Round 1',
            'description' => 'First round of the season',
            'track' => 'International Karting Circuit',
            'event_start_at' => now()->addDays(7),
            'event_end_at' => now()->addDays(7)->addHours(8),
            'hide' => false,
        ]);

        $response = $this->get(route('calendar.races'));

        $content = $response->getContent();
        $this->assertStringContainsString('Championship Round 1', $content);
        $this->assertStringContainsString('International Karting Circuit', $content);
    }

    public function test_calendar_excludes_races_from_ended_championships(): void
    {
        $endedChampionship = Championship::factory()->create([
            'start_at' => now()->subMonths(6),
            'end_at' => now()->subMonth(),
        ]);

        Race::factory()->create([
            'title' => 'Ended Championship Race',
            'event_start_at' => now()->subDays(7),
            'event_end_at' => now()->subDays(7)->addHours(8),
            'hide' => false,
            'canceled_at' => null,
            'championship_id' => $endedChampionship->id,
        ]);

        $response = $this->get(route('calendar.races'));

        $content = $response->getContent();
        $this->assertStringNotContainsString('Ended Championship Race', $content);
    }

    public function test_calendar_includes_races_from_upcoming_championships(): void
    {
        $upcomingChampionship = Championship::factory()->create([
            'start_at' => now()->addMonth(),
            'end_at' => now()->addMonths(6),
        ]);

        Race::factory()->create([
            'title' => 'Upcoming Championship Race',
            'event_start_at' => now()->addMonths(2),
            'event_end_at' => now()->addMonths(2)->addHours(8),
            'hide' => false,
            'canceled_at' => null,
            'championship_id' => $upcomingChampionship->id,
        ]);

        $response = $this->get(route('calendar.races'));

        $content = $response->getContent();
        $this->assertStringContainsString('Upcoming Championship Race', $content);
    }

    public function test_calendar_includes_races_from_championships_without_end_date(): void
    {
        $openEndedChampionship = Championship::factory()->create([
            'start_at' => now()->subMonths(3),
            'end_at' => null,
        ]);

        Race::factory()->create([
            'title' => 'Open-Ended Championship Race',
            'event_start_at' => now()->addDays(7),
            'event_end_at' => now()->addDays(7)->addHours(8),
            'hide' => false,
            'canceled_at' => null,
            'championship_id' => $openEndedChampionship->id,
        ]);

        $response = $this->get(route('calendar.races'));

        $content = $response->getContent();
        $this->assertStringContainsString('Open-Ended Championship Race', $content);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

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

    public function test_calendar_includes_upcoming_visible_races(): void
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

    public function test_calendar_excludes_past_races(): void
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
        $this->assertStringNotContainsString('Past Race', $content);
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
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Championship;
use App\Models\Race;
use App\Models\RunResult;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class ChampionshipRaceCalendarControllerTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_calendar_ics_is_publicly_accessible(): void
    {
        $championship = Championship::factory()->create();

        $response = $this->get(route('calendar.championship.races', [
            'championship' => $championship->uuid,
            'format' => 'ics',
        ]));

        $response->assertOk();
    }

    public function test_calendar_json_is_publicly_accessible(): void
    {
        $championship = Championship::factory()->create();

        $response = $this->get(route('calendar.championship.races', [
            'championship' => $championship->uuid,
            'format' => 'json',
        ]));

        $response->assertOk();
    }

    public function test_invalid_format_returns_404(): void
    {
        $championship = Championship::factory()->create();

        $response = $this->get('/api/championship/'.$championship->uuid.'/races.xml');

        $response->assertNotFound();
    }

    public function test_calendar_ics_returns_ical_content_type(): void
    {
        $championship = Championship::factory()->create();

        $response = $this->get(route('calendar.championship.races', [
            'championship' => $championship->uuid,
            'format' => 'ics',
        ]));

        $response->assertHeader('Content-Type', 'text/calendar; charset=utf-8');
    }

    public function test_calendar_json_returns_json_content_type(): void
    {
        $championship = Championship::factory()->create();

        $response = $this->get(route('calendar.championship.races', [
            'championship' => $championship->uuid,
            'format' => 'json',
        ]));

        $response->assertHeader('Content-Type', 'application/json');
    }

    public function test_calendar_ics_includes_cache_headers(): void
    {
        $championship = Championship::factory()->create();

        $response = $this->get(route('calendar.championship.races', [
            'championship' => $championship->uuid,
            'format' => 'ics',
        ]));

        $response->assertHeader('Cache-Control', 'max-age=3600, public');
        $response->assertHeader('Expires');
    }

    public function test_calendar_ics_includes_visible_races(): void
    {
        $championship = Championship::factory()->create(['title' => 'Test Championship']);

        $race = Race::factory()->create([
            'title' => 'Championship Race 1',
            'championship_id' => $championship->id,
            'event_start_at' => now()->addDays(7),
            'event_end_at' => now()->addDays(7)->addHours(8),
            'hide' => false,
            'canceled_at' => null,
        ]);

        $response = $this->get(route('calendar.championship.races', [
            'championship' => $championship->uuid,
            'format' => 'ics',
        ]));

        $content = $response->getContent();
        $this->assertStringContainsString('Championship Race 1', $content);
        $this->assertStringContainsString('race-'.$race->uuid, $content);
    }

    public function test_calendar_json_includes_visible_races(): void
    {
        $championship = Championship::factory()->create(['title' => 'Test Championship']);

        $race = Race::factory()->create([
            'title' => 'Championship Race 1',
            'championship_id' => $championship->id,
            'event_start_at' => now()->addDays(7),
            'event_end_at' => now()->addDays(7)->addHours(8),
            'hide' => false,
            'canceled_at' => null,
        ]);

        $response = $this->get(route('calendar.championship.races', [
            'championship' => $championship->uuid,
            'format' => 'json',
        ]));

        $response->assertJsonFragment([
            'title' => 'Championship Race 1',
            'uuid' => $race->uuid,
        ]);
    }

    public function test_calendar_ics_excludes_hidden_races(): void
    {
        $championship = Championship::factory()->create();

        Race::factory()->create([
            'title' => 'Hidden Race',
            'championship_id' => $championship->id,
            'event_start_at' => now()->addDays(7),
            'event_end_at' => now()->addDays(7)->addHours(8),
            'hide' => true,
            'canceled_at' => null,
        ]);

        $response = $this->get(route('calendar.championship.races', [
            'championship' => $championship->uuid,
            'format' => 'ics',
        ]));

        $content = $response->getContent();
        $this->assertStringNotContainsString('Hidden Race', $content);
    }

    public function test_calendar_json_excludes_hidden_races(): void
    {
        $championship = Championship::factory()->create();

        Race::factory()->create([
            'title' => 'Hidden Race',
            'championship_id' => $championship->id,
            'event_start_at' => now()->addDays(7),
            'event_end_at' => now()->addDays(7)->addHours(8),
            'hide' => true,
            'canceled_at' => null,
        ]);

        $response = $this->get(route('calendar.championship.races', [
            'championship' => $championship->uuid,
            'format' => 'json',
        ]));

        $response->assertJsonMissing(['title' => 'Hidden Race']);
    }

    public function test_calendar_ics_excludes_cancelled_races(): void
    {
        $championship = Championship::factory()->create();

        Race::factory()->cancelled()->create([
            'title' => 'Cancelled Race',
            'championship_id' => $championship->id,
            'event_start_at' => now()->addDays(7),
            'event_end_at' => now()->addDays(7)->addHours(8),
            'hide' => false,
        ]);

        $response = $this->get(route('calendar.championship.races', [
            'championship' => $championship->uuid,
            'format' => 'ics',
        ]));

        $content = $response->getContent();
        $this->assertStringNotContainsString('Cancelled Race', $content);
    }

    public function test_calendar_json_excludes_cancelled_races(): void
    {
        $championship = Championship::factory()->create();

        Race::factory()->cancelled()->create([
            'title' => 'Cancelled Race',
            'championship_id' => $championship->id,
            'event_start_at' => now()->addDays(7),
            'event_end_at' => now()->addDays(7)->addHours(8),
            'hide' => false,
        ]);

        $response = $this->get(route('calendar.championship.races', [
            'championship' => $championship->uuid,
            'format' => 'json',
        ]));

        $response->assertJsonMissing(['title' => 'Cancelled Race']);
    }

    public function test_calendar_ics_contains_valid_ical_structure(): void
    {
        $championship = Championship::factory()->create();

        Race::factory()->create([
            'championship_id' => $championship->id,
            'event_start_at' => now()->addDays(7),
            'event_end_at' => now()->addDays(7)->addHours(8),
            'hide' => false,
        ]);

        $response = $this->get(route('calendar.championship.races', [
            'championship' => $championship->uuid,
            'format' => 'ics',
        ]));

        $content = $response->getContent();
        $this->assertStringContainsString('BEGIN:VCALENDAR', $content);
        $this->assertStringContainsString('END:VCALENDAR', $content);
        $this->assertStringContainsString('BEGIN:VEVENT', $content);
        $this->assertStringContainsString('END:VEVENT', $content);
    }

    public function test_calendar_ics_includes_race_details(): void
    {
        $championship = Championship::factory()->create();

        Race::factory()->create([
            'title' => 'Championship Round 1',
            'description' => 'First round of the season',
            'track' => 'International Karting Circuit',
            'championship_id' => $championship->id,
            'event_start_at' => now()->addDays(7),
            'event_end_at' => now()->addDays(7)->addHours(8),
            'hide' => false,
        ]);

        $response = $this->get(route('calendar.championship.races', [
            'championship' => $championship->uuid,
            'format' => 'ics',
        ]));

        $content = $response->getContent();
        $this->assertStringContainsString('Championship Round 1', $content);
        $this->assertStringContainsString('International Karting Circuit', $content);
    }

    public function test_calendar_json_includes_race_details(): void
    {
        $championship = Championship::factory()->create(['title' => 'Test Championship']);

        Race::factory()->create([
            'title' => 'Championship Round 1',
            'description' => 'First round of the season',
            'track' => 'International Karting Circuit',
            'championship_id' => $championship->id,
            'event_start_at' => now()->addDays(7),
            'event_end_at' => now()->addDays(7)->addHours(8),
            'hide' => false,
        ]);

        $response = $this->get(route('calendar.championship.races', [
            'championship' => $championship->uuid,
            'format' => 'json',
        ]));

        $response->assertJsonFragment([
            'title' => 'Championship Round 1',
            'description' => 'First round of the season',
            'track' => 'International Karting Circuit',
        ]);
    }

    public function test_calendar_only_includes_races_from_specified_championship(): void
    {
        $championship1 = Championship::factory()->create(['title' => 'Championship 1']);
        $championship2 = Championship::factory()->create(['title' => 'Championship 2']);

        Race::factory()->create([
            'title' => 'Championship 1 Race',
            'championship_id' => $championship1->id,
            'event_start_at' => now()->addDays(7),
            'event_end_at' => now()->addDays(7)->addHours(8),
            'hide' => false,
        ]);

        Race::factory()->create([
            'title' => 'Championship 2 Race',
            'championship_id' => $championship2->id,
            'event_start_at' => now()->addDays(7),
            'event_end_at' => now()->addDays(7)->addHours(8),
            'hide' => false,
        ]);

        $response = $this->get(route('calendar.championship.races', [
            'championship' => $championship1->uuid,
            'format' => 'ics',
        ]));

        $content = $response->getContent();
        $this->assertStringContainsString('Championship 1 Race', $content);
        $this->assertStringNotContainsString('Championship 2 Race', $content);
    }

    public function test_calendar_json_only_includes_races_from_specified_championship(): void
    {
        $championship1 = Championship::factory()->create(['title' => 'Championship 1']);
        $championship2 = Championship::factory()->create(['title' => 'Championship 2']);

        Race::factory()->create([
            'title' => 'Championship 1 Race',
            'championship_id' => $championship1->id,
            'event_start_at' => now()->addDays(7),
            'event_end_at' => now()->addDays(7)->addHours(8),
            'hide' => false,
        ]);

        Race::factory()->create([
            'title' => 'Championship 2 Race',
            'championship_id' => $championship2->id,
            'event_start_at' => now()->addDays(7),
            'event_end_at' => now()->addDays(7)->addHours(8),
            'hide' => false,
        ]);

        $response = $this->get(route('calendar.championship.races', [
            'championship' => $championship1->uuid,
            'format' => 'json',
        ]));

        $response->assertJsonFragment(['title' => 'Championship 1 Race']);
        $response->assertJsonMissing(['title' => 'Championship 2 Race']);
    }

    public function test_calendar_json_includes_championship_information(): void
    {
        $championship = Championship::factory()->create(['title' => 'Test Championship']);

        Race::factory()->create([
            'championship_id' => $championship->id,
            'event_start_at' => now()->addDays(7),
            'event_end_at' => now()->addDays(7)->addHours(8),
            'hide' => false,
        ]);

        $response = $this->get(route('calendar.championship.races', [
            'championship' => $championship->uuid,
            'format' => 'json',
        ]));

        $response->assertJsonFragment([
            'championship' => [
                'uuid' => $championship->uuid,
                'title' => 'Test Championship',
            ],
        ]);
    }

    public function test_calendar_json_includes_registration_url(): void
    {
        $championship = Championship::factory()->create();

        $race = Race::factory()->create([
            'championship_id' => $championship->id,
            'event_start_at' => now()->addDays(7),
            'event_end_at' => now()->addDays(7)->addHours(8),
            'hide' => false,
        ]);

        $response = $this->get(route('calendar.championship.races', [
            'championship' => $championship->uuid,
            'format' => 'json',
        ]));

        $response->assertJsonFragment([
            'registration_url' => route('races.registration.create', $race->uuid),
        ]);
    }

    public function test_calendar_races_are_ordered_by_event_start_date(): void
    {
        $championship = Championship::factory()->create();

        $race1 = Race::factory()->create([
            'title' => 'Race 1',
            'championship_id' => $championship->id,
            'event_start_at' => now()->addDays(14),
            'event_end_at' => now()->addDays(14)->addHours(8),
            'hide' => false,
        ]);

        $race2 = Race::factory()->create([
            'title' => 'Race 2',
            'championship_id' => $championship->id,
            'event_start_at' => now()->addDays(7),
            'event_end_at' => now()->addDays(7)->addHours(8),
            'hide' => false,
        ]);

        $race3 = Race::factory()->create([
            'title' => 'Race 3',
            'championship_id' => $championship->id,
            'event_start_at' => now()->addDays(21),
            'event_end_at' => now()->addDays(21)->addHours(8),
            'hide' => false,
        ]);

        $response = $this->get(route('calendar.championship.races', [
            'championship' => $championship->uuid,
            'format' => 'json',
        ]));

        $data = $response->json('data');
        $this->assertEquals('Race 2', $data[0]['title']);
        $this->assertEquals('Race 1', $data[1]['title']);
        $this->assertEquals('Race 3', $data[2]['title']);
    }

    public function test_calendar_ics_includes_championship_title_in_calendar_name(): void
    {
        $championship = Championship::factory()->create(['title' => 'My Championship 2026']);

        $response = $this->get(route('calendar.championship.races', [
            'championship' => $championship->uuid,
            'format' => 'ics',
        ]));

        $content = $response->getContent();
        $this->assertStringContainsString('My Championship 2026', $content);
    }

    public function test_calendar_json_includes_results_url_when_published_results_exist(): void
    {
        $championship = Championship::factory()->create();

        $race = Race::factory()->create([
            'championship_id' => $championship->id,
            'event_start_at' => now()->addDays(7),
            'event_end_at' => now()->addDays(7)->addHours(8),
            'hide' => false,
        ]);

        RunResult::factory()->published()->create([
            'race_id' => $race->getKey(),
        ]);

        $response = $this->get(route('calendar.championship.races', [
            'championship' => $championship->uuid,
            'format' => 'json',
        ]));

        $response->assertJsonFragment([
            'results_url' => route('public.races.results.index', $race->uuid),
        ]);
    }

    public function test_calendar_json_returns_null_results_url_when_no_published_results(): void
    {
        $championship = Championship::factory()->create();

        Race::factory()->create([
            'championship_id' => $championship->id,
            'event_start_at' => now()->addDays(7),
            'event_end_at' => now()->addDays(7)->addHours(8),
            'hide' => false,
        ]);

        $response = $this->get(route('calendar.championship.races', [
            'championship' => $championship->uuid,
            'format' => 'json',
        ]));

        $response->assertJsonFragment([
            'results_url' => null,
        ]);
    }

    public function test_calendar_ics_filename_uses_slugified_championship_title(): void
    {
        $championship = Championship::factory()->create(['title' => 'My Championship 2026']);

        $response = $this->get(route('calendar.championship.races', [
            'championship' => $championship->uuid,
            'format' => 'ics',
        ]));

        $response->assertHeader('Content-Disposition', 'attachment; filename="my-championship-2026-races-calendar.ics"');
    }
}

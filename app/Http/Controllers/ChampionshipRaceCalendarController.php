<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\RaceResource;
use App\Models\Championship;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Components\Event;

class ChampionshipRaceCalendarController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function show(Championship $championship, string $format)
    {
        $races = $this->getRaces($championship);

        return match ($format) {
            'ics' => $this->respondWithIcs($championship, $races),
            'json' => $this->respondWithJson($races),
            default => abort(404),
        };
    }

    /**
     * Respond with ICS format.
     */
    private function respondWithIcs(Championship $championship, $races)
    {
        $calendar = Calendar::create($championship->title)
            ->description(__('Races for :championship', ['championship' => $championship->title]))
            ->productIdentifier('kart-race-management')
            ->refreshInterval(60 * Carbon::HOURS_PER_DAY);

        foreach ($races as $race) {
            $event = Event::create()
                ->name($race->title)
                ->uniqueIdentifier('race-'.$race->uuid)
                ->description($this->buildDescription($race))
                ->startsAt($race->event_start_at)
                ->endsAt($race->event_end_at);

            if ($race->track) {
                $event->address($race->track);
            }

            if ($registrationUrl = route('races.registration.create', $race->uuid)) {
                $event->url($registrationUrl);
            }

            $calendar->event($event);
        }

        $filename = Str::slug($championship->title).'-races-calendar.ics';

        return response($calendar->get())
            ->header('Content-Type', 'text/calendar; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"')
            ->header('Cache-Control', 'public, max-age=3600')
            ->header('Expires', now()->addDay()->toRfc7231String());
    }

    /**
     * Respond with JSON format.
     */
    private function respondWithJson($races)
    {
        return RaceResource::collection($races);
    }

    /**
     * Get races for the championship.
     */
    private function getRaces(Championship $championship)
    {
        return $championship->races()
            ->visible()
            ->notCanceled()
            ->withCount(['results as published_results_count' => function ($query) {
                $query->whereNotNull('published_at');
            }])
            ->orderBy('event_start_at')
            ->get();
    }

    /**
     * Build the event description with race details.
     */
    private function buildDescription($race): string
    {
        $parts = [];

        if ($race->description) {
            $parts[] = $race->description;
            $parts[] = '';
        }

        $parts[] = __('Championship: :champ', ['champ' => $race->championship->title]);

        if ($race->registration_opens_at && $race->registration_closes_at) {
            $parts[] = __('Registration: :dates', ['dates' => $race->registration_opens_at->format('Y-m-d').
                ' - '.
                $race->registration_closes_at->format('Y-m-d')]);
        }

        if ($race->published_results_count > 0) {
            $parts[] = __('Results: ').route('public.races.results.index', $race->uuid);
        }

        $parts[] = '';
        $parts[] = __('Register at: ').route('races.registration.create', $race->uuid);

        return implode("\n", $parts);
    }
}

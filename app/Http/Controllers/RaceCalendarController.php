<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Race;
use Illuminate\Support\Carbon;
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Components\Event;

class RaceCalendarController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @return \Illuminate\Http\Response
     */
    public function __invoke()
    {
        $races = Race::query()
            ->visible()
            ->notCanceled()
            ->whereHas('championship', function ($query) {
                $query->where('end_at', '>=', now())
                    ->orWhereNull('end_at');
            })
            ->orderBy('event_start_at')
            ->with('championship')
            ->get();

        $calendar = Calendar::create(__('Race Calendar'))
            ->description(config('races.organizer.name') ? __('Upcoming races organized by :organizer', ['organizer' => config('races.organizer.name')]) : __('Upcoming races calendar'))
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

        return response($calendar->get())
            ->header('Content-Type', 'text/calendar; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="races-calendar.ics"')
            ->header('Cache-Control', 'public, max-age=3600')
            ->header('Expires', now()->addDay()->toRfc7231String());
    }

    /**
     * Build the event description with race details.
     */
    private function buildDescription(Race $race): string
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

        $parts[] = '';
        $parts[] = __('Register at: ').route('races.registration.create', $race->uuid);

        return implode("\n", $parts);
    }
}

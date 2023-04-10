<?php

namespace App\Http\Controllers;

use App\Models\Championship;
use App\Models\Race;
use App\Models\RaceType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class RaceInChampionshipController extends Controller
{
    /**
     * Create the controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->authorizeResource(Race::class, 'race');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Championship $championship)
    {
        return view('championship.race.index', [
            'championship' => $championship,
            'races' => $championship->races,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Championship $championship)
    {
        return view('championship.race.create', [
            'championship' => $championship,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Championship $championship, Request $request)
    {
        $validated = $this->validate($request, [
            'start' => 'required|date|after:today',
            'end' => 'nullable|date|after:event_start',
            'title' => 'required|string|max:250|unique:' . Race::class .',title',
            'description' => 'nullable|string|max:1000',
            'track' => 'required|string|max:250',
            'hidden' => 'nullable|in:true,false',
            'participants_total_limit' => 'nullable|integer|min:1',
            'race_type' => ['nullable', 'integer', new Enum(RaceType::class)],
        ]);

        $configuredStartTime = config('races.start_time');
        $configuredEndTime = config('races.end_time');

        $start_date = Carbon::parse("{$validated['start']} {$configuredStartTime}", config('races.timezone'));
        $end_date = $validated['end'] ? Carbon::parse("{$validated['end']} {$configuredEndTime}", config('races.timezone')) : $start_date->copy()->setTimeFromTimeString($configuredEndTime);

        $utc_start_date = $start_date->setTimezone(config('app.timezone'));
        $utc_end_date = $end_date->setTimezone(config('app.timezone'));

        $race = $championship->races()->create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'event_start_at' => $utc_start_date,
            'event_end_at' => $utc_end_date,
            'track' => $validated['track'],
            'registration_opens_at' => $utc_start_date->copy()->subHours(config('races.registration.opens')),
            'registration_closes_at' => $utc_start_date->copy()->subHours(config('races.registration.closes')),
            'hide' => ($validated['hidden'] ?? '') === 'true' ? true : false,
            'participant_limits' => ($validated['participants_total_limit'] ?? false) ? ['total' => $validated['participants_total_limit']] : null,
            'type' => $validated['race_type'] ?? RaceType::LOCAL,
        ]);

        return to_route('championships.races.index', $championship)
            ->with('flash.banner', __(':race created.', [
                'race' => $race->title
            ]));
    }

    
}

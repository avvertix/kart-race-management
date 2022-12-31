<?php

namespace App\Http\Controllers;

use App\Models\Championship;
use App\Models\Race;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
        ]);

        $configuredStartTime = config('races.start_time');
        $configuredEndTime = config('races.end_time');

        $start_date = Carbon::parse("{$validated['start']} {$configuredStartTime}");
        $end_date = $validated['end'] ? Carbon::parse("{$validated['end']} {$configuredEndTime}") : $start_date->copy()->setTimeFromTimeString($configuredEndTime);


        $race = $championship->races()->create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'event_start_at' => $start_date,
            'event_end_at' => $end_date,
            'track' => $validated['track'],
            'registration_opens_at' => $start_date->copy()->subHours(config('races.registration.opens')),
            'registration_closes_at' => $start_date->copy()->subHours(config('races.registration.closes')),
        ]);

        return to_route('championships.races.index', $championship)
            ->with('message', __(':race created.', [
                'race' => $race->title
            ]));
    }

    
}

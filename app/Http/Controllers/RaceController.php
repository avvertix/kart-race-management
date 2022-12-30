<?php

namespace App\Http\Controllers;

use App\Models\Championship;
use App\Models\Race;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RaceController extends Controller
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

        $start_date = Carbon::parse($validated['start']);
        $end_date = $validated['end'] ? Carbon::parse($validated['end']) : $start_date->copy()->endOfDay();


        $race = $championship->races()->create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'event_start_at' => $start_date,
            'event_end_at' => $end_date,
            'track' => $validated['track'],
        ]);

        return to_route('championships.races.index', $championship)
            ->with('message', __(':race created.', [
                'race' => $race->title
            ]));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Race  $race
     * @return \Illuminate\Http\Response
     */
    public function show(Race $race)
    {
        return view('championship.race.show', [
            'race' => $race,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Race  $race
     * @return \Illuminate\Http\Response
     */
    public function edit(Race $race)
    {
        return view('championship.race.edit', [
            'championship' => $race->championship,
            'race' => $race,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Race  $race
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Race $race)
    {
        $validated = $this->validate($request, [
            'start' => 'required|date|after:today',
            'end' => 'nullable|date|after:event_start',
            'title' => ['required', 'string', 'max:250', Rule::unique((new Race())->getTable(), 'title')->ignore($race)],
            'description' => 'nullable|string|max:1000',
            'track' => 'required|string|max:250',
        ]);

        $start_date = Carbon::parse($validated['start']);
        $end_date = $validated['end'] ? Carbon::parse($validated['end']) : $start_date->copy()->endOfDay();


        $race->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'event_start_at' => $start_date,
            'event_end_at' => $end_date,
            'track' => $validated['track'],
        ]);

        return to_route('races.show', $race)
            ->with('message', __(':race saved.', [
                'race' => $validated['title']
            ]));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Race  $race
     * @return \Illuminate\Http\Response
     */
    public function destroy(Race $race)
    {
        //
    }
}
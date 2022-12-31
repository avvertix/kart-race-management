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
    public function index()
    {
        return view('race.index', [
            'races' => Race::all(),
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Race  $race
     * @return \Illuminate\Http\Response
     */
    public function show(Race $race)
    {
        return view('race.show', [
            'race' => $race,
            'championship' => $race->championship,
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
        return view('race.edit', [
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

        $configuredStartTime = config('races.start_time');
        $configuredEndTime = config('races.end_time');

        $start_date = Carbon::parse("{$validated['start']} {$configuredStartTime}");
        $end_date = $validated['end'] ? Carbon::parse("{$validated['end']} {$configuredEndTime}") : $start_date->copy()->setTimeFromTimeString($configuredEndTime);


        $race->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'event_start_at' => $start_date,
            'event_end_at' => $end_date,
            'track' => $validated['track'],
            'registration_opens_at' => $start_date->copy()->subHours(config('races.registration.opens')),
            'registration_closes_at' => $start_date->copy()->subHours(config('races.registration.closes')),
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

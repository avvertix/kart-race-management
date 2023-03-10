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
        $statistics = $race->participants()
            ->selectRaw('count(*) as total')
            ->selectRaw("count(case when confirmed_at is not null then 1 end) as confirmed")
            ->first();
        
        $statistics->transponders = $race->participants()->has('transponders')->count();

        $participantsPerCategory = $race->participants()
            ->selectRaw('category, count(*) as total')
            ->groupBy('category')
            ->get();

        return view('race.show', [
            'race' => $race,
            'championship' => $race->championship,
            'statistics' => $statistics,
            'participantsPerCategory' => $participantsPerCategory,
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
            'hidden' => 'nullable|in:true,false',
        ]);

        $configuredStartTime = config('races.start_time');
        $configuredEndTime = config('races.end_time');

        $start_date = Carbon::parse("{$validated['start']} {$configuredStartTime}", config('races.timezone'));
        $end_date = $validated['end'] ? Carbon::parse("{$validated['end']} {$configuredEndTime}", config('races.timezone')) : $start_date->copy()->setTimeFromTimeString($configuredEndTime);

        $utc_start_date = $start_date->setTimezone(config('app.timezone'));
        $utc_end_date = $end_date->setTimezone(config('app.timezone'));


        $race->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'event_start_at' => $utc_start_date,
            'event_end_at' => $utc_end_date,
            'track' => $validated['track'],
            'registration_opens_at' => $utc_start_date->copy()->subHours(config('races.registration.opens')),
            'registration_closes_at' => $utc_start_date->copy()->subHours(config('races.registration.closes')),
            'hide' => ($validated['hidden'] ?? '') === 'true' ? true : false,
        ]);

        return to_route('races.show', $race)
            ->with('flash.banner', __(':race saved.', [
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

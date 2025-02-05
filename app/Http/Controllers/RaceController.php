<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Race;
use App\Models\RaceType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

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
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Race $race)
    {
        $statistics = $race->participants()
            ->selectRaw('count(*) as total')
            ->selectRaw('count(case when confirmed_at is not null then 1 end) as confirmed')
            ->first();

        $statistics->transponders = $race->participants()->has('transponders')->count();

        $participantsPerCategory = $race->participants()
            ->selectRaw('category_id, count(*) as total, count(confirmed_at) as total_confirmed')
            ->groupBy('category_id')
            ->with('racingCategory')
            ->get();

        $participantsPerEngine = DB::query()
            ->fromRaw("participants, JSON_TABLE(vehicles, '$[*].engine_manufacturer' COLUMNS (engine_manufacturer TEXT PATH '$')) AS jt")
            ->selectRaw('jt.engine_manufacturer, COUNT(*) AS total')
            ->groupBy(['jt.engine_manufacturer'])
            ->whereNotNull('jt.engine_manufacturer')
            ->where('race_id', $race->getKey())
            ->orderBy('jt.engine_manufacturer')
            ->get();

        return view('race.show', [
            'race' => $race,
            'championship' => $race->championship,
            'statistics' => $statistics,
            'participantsPerCategory' => $participantsPerCategory,
            'participantsPerEngine' => $participantsPerEngine,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
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
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Race $race)
    {
        $validated = $this->validate($request, [
            'start' => 'required|date|after_or_equal:today',
            'end' => 'nullable|date|after_or_equal:start',
            'title' => ['required', 'string', 'max:250', Rule::unique((new Race())->getTable(), 'title')->ignore($race)],
            'description' => 'nullable|string|max:1000',
            'track' => 'required|string|max:250',
            'hidden' => 'nullable|in:true,false',
            'participants_total_limit' => 'nullable|integer|min:1',
            'race_type' => ['nullable', 'integer', new Enum(RaceType::class)],
            'registration_opens_at' => ['nullable', 'date', 'before:start'],
            'registration_closes_at' => ['nullable', 'date', 'after:registration_opens_at'],
        ]);

        $configuredStartTime = config('races.start_time');
        $configuredEndTime = config('races.end_time');

        $start_date = Carbon::parse("{$validated['start']} {$configuredStartTime}", config('races.timezone'));
        $end_date = $validated['end'] ? Carbon::parse("{$validated['end']} {$configuredEndTime}", config('races.timezone')) : $start_date->copy()->setTimeFromTimeString($configuredEndTime);

        $utc_start_date = $start_date->setTimezone(config('app.timezone'));
        $utc_end_date = $end_date->setTimezone(config('app.timezone'));

        $utc_registration_opens_at = ($validated['registration_opens_at'] ?? false) ? Carbon::parse($validated['registration_opens_at'], config('races.timezone'))->setTimezone(config('app.timezone')) : null;
        $utc_registration_closes_at = ($validated['registration_closes_at'] ?? false) ? Carbon::parse($validated['registration_closes_at'], config('races.timezone'))->setTimezone(config('app.timezone')) : null;

        $race->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'event_start_at' => $utc_start_date,
            'event_end_at' => $utc_end_date,
            'track' => $validated['track'],
            'registration_opens_at' => $utc_registration_opens_at ?? $utc_start_date->copy()->subHours(config('races.registration.opens')),
            'registration_closes_at' => $utc_registration_closes_at ?? $utc_start_date->copy()->subHours(config('races.registration.closes')),
            'hide' => ($validated['hidden'] ?? '') === 'true' ? true : false,
            'participant_limits' => $validated['participants_total_limit'] ? ($race->participant_limits ?? collect())->merge(['total' => $validated['participants_total_limit']]) : null,
            'type' => $validated['race_type'] ?? RaceType::LOCAL,
        ]);

        return to_route('races.show', $race)
            ->with('flash.banner', __(':race saved.', [
                'race' => $validated['title'],
            ]));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Race $race)
    {
        $race->canceled_at = now();
        $race->save();

        return to_route('races.show', $race)
            ->with('flash.banner', __(':race canceled.', [
                'race' => $race->title,
            ]));
    }
}

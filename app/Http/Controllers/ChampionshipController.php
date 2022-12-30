<?php

namespace App\Http\Controllers;

use App\Models\Championship;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ChampionshipController extends Controller
{
    /**
     * Create the controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->authorizeResource(Championship::class, 'championship');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $championships = Championship::query()->orderByDesc('start_at')->paginate();

        return view('championship.index', [
            'championships' => $championships,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('championship.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $this->validate($request, [
            'start' => 'required|date|after:yesterday',
            'end' => 'nullable|date|after:start',
            'title' => 'nullable|string|max:250|unique:' . Championship::class .',title',
            'description' => 'nullable|string|max:1000',
        ]);

        $start_date = Carbon::parse($validated['start']);
        $end_date = $validated['end'] ? Carbon::parse($validated['end']) : null;

        $championship = Championship::create([
            'title' => $validated['title'] ?? __(':year Championship', ['year' => $start_date->year]),
            'description' => $validated['description'],
            'start_at' => $start_date,
            'end_at' => $end_date,
        ]);

        return to_route('championships.index')
            ->with('message', __(':championship created.', [
                'championship' => $championship->title
            ]));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Championship  $championship
     * @return \Illuminate\Http\Response
     */
    public function show(Championship $championship)
    {
        return view('championship.show', ['championship' => $championship]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Championship  $championship
     * @return \Illuminate\Http\Response
     */
    public function edit(Championship $championship)
    {
        return view('championship.edit', ['championship' => $championship]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Championship  $championship
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Championship $championship)
    {
        // update is permitted unless races are defined
        // if races are there no changes can be made

        if($championship->races()->exists()){
            throw ValidationException::withMessages(['races' => __('Championship contains races. Update is allowed only when no races are present.')]);
        }

        $validated = $this->validate($request, [
            'start' => 'required|date|after:yesterday',
            'end' => 'nullable|date|after:start',
            'title' => ['nullable', 'string', 'max:250', Rule::unique((new Championship())->getTable(), 'title')->ignore($championship)],
            'description' => 'nullable|string|max:1000',
        ]);

        $start_date = Carbon::parse($validated['start']);
        $end_date = $validated['end'] ? Carbon::parse($validated['end']) : null;

        $championship->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'start_at' => $start_date,
            'end_at' => $end_date,
        ]);

        return to_route('championships.show', $championship)
            ->with('message', __(':championship updated.', [
                'championship' => $validated['title']
            ]));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Championship  $championship
     * @return \Illuminate\Http\Response
     */
    public function destroy(Championship $championship)
    {
        //
    }
}

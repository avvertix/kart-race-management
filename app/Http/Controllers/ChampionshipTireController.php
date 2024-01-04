<?php

namespace App\Http\Controllers;

use App\Models\Championship;
use App\Models\ChampionshipTire;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ChampionshipTireController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(ChampionshipTire::class, 'tire_option');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Championship $championship)
    {
        return view('championship-tire.index', [
            'championship' => $championship,
            'tires' => $championship->tires()->orderBy('name', 'ASC')->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Championship $championship)
    {
        return view('championship-tire.create', [
            'championship' => $championship,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Championship $championship)
    {
        $validated = $this->validate($request, [
            'name' => 'required|string|max:250|unique:' . ChampionshipTire::class .',name',
            'price' => 'required|integer|min:0',
        ]);

        $tire = $championship->tires()->create([
            'name' => $validated['name'],
            'price' => $request->integer('price'),
        ]);

        return redirect()->route('championships.tire-options.index', $championship)
            ->with('flash.banner', __(':tire created.', [
                'tire' => $tire->name
            ]));
    }

    /**
     * Display the specified resource.
     */
    public function show(ChampionshipTire $tireOption)
    {
        return view('championship-tire.show', [
            'tire' => $tireOption,
            'championship' => $tireOption->championship,
            'activities' => $tireOption->activities,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ChampionshipTire $tireOption)
    {
        return view('championship-tire.edit', [
            'tire' => $tireOption,
            'championship' => $tireOption->championship,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ChampionshipTire $tireOption)
    {
        $validated = $this->validate($request, [
            'name' => ['required','string','max:250', Rule::unique((new ChampionshipTire())->getTable(), 'name')->ignore($tireOption)],
            'price' => 'required|integer|min:0',
        ]);

        $tireOption->update([
            'name' => $validated['name'],
            'price' => $request->integer('price'),
        ]);

        return redirect()->route('championships.tire-options.index', $tireOption->championship)
            ->with('flash.banner', __(':tire updated.', [
                'tire' => $tireOption->name
            ]));
    }
}

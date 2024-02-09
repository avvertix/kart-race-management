<?php

namespace App\Http\Controllers;

use App\Models\Bonus;
use App\Models\BonusType;
use App\Models\Championship;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class ChampionshipBonusController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Bonus::class, 'bonus');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Championship $championship)
    {
        return view('bonus.index', [
            'championship' => $championship,
            'bonuses' => $championship->bonuses()->orderBy('driver', 'ASC')->orderBy('amount', 'DESC')->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Championship $championship)
    {
        return view('bonus.create', [
            'championship' => $championship,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Championship $championship)
    {
        $validated = $this->validate($request, [
            'driver' => 'required|string|max:250',
            'driver_licence' => 'required|string|max:250',
            'bonus_type' => ['required', 'integer', new Enum(BonusType::class)],
            'amount' => 'required|integer|min:1',
        ]);

        $bonus = $championship->bonuses()->create([
            'driver' => $validated['driver'],
            'driver_licence' => $validated['driver_licence'] ?? null,
            'driver_licence_hash' => hash('sha512', $validated['driver_licence']),
            'amount' => $validated['amount'],
            'bonus_type' => BonusType::from($validated['bonus_type']),
        ]);

        return redirect()->route('championships.bonuses.index', $championship)
            ->with('flash.banner', __('Bonus activated for :driver.', [
                'driver' => $bonus->driver
            ]));
    }

    /**
     * Display the specified resource.
     */
    public function show(Bonus $bonus)
    {
        $bonus->load('championship');
        
        return view('bonus.show', [
            'bonus' => $bonus,
            'championship' => $bonus->championship,
            'bonusUsage' => collect(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Bonus $bonus)
    {
        $bonus->load('championship');
        
        return view('bonus.edit', [
            'bonus' => $bonus,
            'championship' => $bonus->championship,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Bonus $bonus)
    {
        $championship = $bonus->championship;

        $validated = $this->validate($request, [
            'driver' => 'required|string|max:250',
            'driver_licence' => 'required|string|max:250',
            'bonus_type' => ['required', 'integer', new Enum(BonusType::class)],
            'amount' => 'required|integer|min:1',
        ]);

        $bonus->update([
            'driver' => $validated['driver'],
            'driver_licence' => $validated['driver_licence'] ?? null,
            'driver_licence_hash' => hash('sha512', $validated['driver_licence']),
            'amount' => $validated['amount'],
            'bonus_type' => BonusType::from($validated['bonus_type']),
        ]);

        return redirect()->route('championships.bonuses.index', $championship)
            ->with('flash.banner', __('Bonus for :driver updated.', [
                'driver' => $bonus->driver
            ]));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Bonus $bonus)
    {
        //
    }
}

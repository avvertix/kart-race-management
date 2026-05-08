<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Championship;
use App\Models\ChampionshipPenalty;
use Illuminate\Http\Request;

class ChampionshipPenaltyController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(ChampionshipPenalty::class, 'penalty');
    }

    public function index(Championship $championship)
    {
        return view('championship.penalty.index', [
            'championship' => $championship,
            'penalties' => $championship->penalties()->get(),
        ]);
    }

    public function create(Championship $championship)
    {
        return view('championship.penalty.create', [
            'championship' => $championship,
        ]);
    }

    public function store(Request $request, Championship $championship)
    {
        $validated = $this->validate($request, [
            'title' => ['required', 'string', 'max:250'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $championship->penalties()->create($validated);

        return redirect()->route('championships.penalties.index', $championship)
            ->with('flash.banner', __('Penalty template created.'));
    }

    public function edit(ChampionshipPenalty $penalty)
    {
        return view('championship.penalty.edit', [
            'championship' => $penalty->championship,
            'penalty' => $penalty,
        ]);
    }

    public function update(Request $request, ChampionshipPenalty $penalty)
    {
        $validated = $this->validate($request, [
            'title' => ['required', 'string', 'max:250'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $penalty->update($validated);

        return redirect()->route('championships.penalties.index', $penalty->championship)
            ->with('flash.banner', __('Penalty template updated.'));
    }

    public function destroy(ChampionshipPenalty $penalty)
    {
        $championship = $penalty->championship;

        $penalty->delete();

        return redirect()->route('championships.penalties.index', $championship)
            ->with('flash.banner', __('Penalty template deleted.'));
    }
}

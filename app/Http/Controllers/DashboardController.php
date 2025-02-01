<?php

namespace App\Http\Controllers;

use App\Models\Championship;
use App\Models\Race;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {

        $races = Race::query()
            ->nextRaces()
            ->orderBy('event_start_at')
            ->withCount('participants')
            ->take(5)
            ->get();

        $championships = Championship::query()
            ->withCount('races')
            ->where('start_at', '>=', today()->startOfYear())
            ->orderByDesc('start_at')
            ->take(5)
            ->get();

        return view('dashboard', [
            'races' => $races,
            'championships' => $championships,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Race;
use App\Models\Tire;
use App\Models\TireOption;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class RaceTranspondersController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Race  $race
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request, Race $race)
    {
        $this->authorize('viewAny', Tire::class);

        $race->load(['championship']);


        // List participants with tires

        $participants = $race->participants()
            ->withCount('transponders')
            ->has('transponders')
            ->with('transponders')
            ->select('participants.*')
            ->join('transponders', 'transponders.participant_id', '=', 'participants.id')
            ->orderBy('transponders.code', 'desc')
            ->orderBy('bib', 'asc')
            ->distinct()
            ->get();

        return view('race.transponders', [
            'race' => $race,
            'championship' => $race->championship,
            'participants' => $participants,
        ]);
    }
    
}

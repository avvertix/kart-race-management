<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Race;
use App\Models\Transponder;
use Illuminate\Http\Request;

class RaceTranspondersController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request, Race $race)
    {
        $this->authorize('viewAny', Transponder::class);

        $race->load(['championship']);

        // List participants with transponder

        $participants = $race->participants()
            ->withCount('transponders')
            ->with('transponders')
            ->select('participants.*')
            ->join('transponders', 'transponders.participant_id', '=', 'participants.id')
            ->orderByRaw('CAST(`code` as unsigned) DESC')
            ->distinct()
            ->get();

        return view('race.transponders', [
            'race' => $race,
            'championship' => $race->championship,
            'participants' => $participants,
        ]);
    }
}

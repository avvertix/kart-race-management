<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Race;
use Illuminate\Http\Request;

class PrintRaceParticipantsController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request, Race $race)
    {
        $race->load(['championship']);

        $participants = $race->participants()
            ->withCount('tires')
            ->orderBy('bib', 'asc')
            ->get();

        return view('participant.print', [
            'race' => $race,
            'championship' => $race->championship,
            'participants' => $participants,
        ]);
    }
}

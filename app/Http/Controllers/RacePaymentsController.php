<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Race;
use Illuminate\Http\Request;

class RacePaymentsController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request, Race $race)
    {
        $this->authorize('update', $race);

        $race->load(['championship']);

        $participants = $race->participants()
            ->with('payments')
            ->orderBy('bib', 'asc')
            ->get();

        return view('race.payments', [
            'race' => $race,
            'championship' => $race->championship,
            'participants' => $participants,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Race;
use App\Models\RaceCommunication;
use Illuminate\Http\Request;

class RaceCommunicationsController extends Controller
{
    public function __invoke(Request $request, Race $race)
    {
        $this->authorize('viewAny', RaceCommunication::class);

        $race->load(['championship']);

        return view('race.communications', [
            'race' => $race,
            'championship' => $race->championship,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Exports\RaceParticipantsExport;
use App\Exports\RaceParticipantsForTimingExport;
use App\Models\Race;
use App\Models\Transponder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ExportRaceParticipantsForTimingController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request, Race $race)
    {
        $this->authorize('create', Transponder::class);

        $filename = Str::slug('mylaps-' . config('races.organizer.name') . '-' . $race->event_start_at->toDateString() . '-' . $race->title);

        return (new RaceParticipantsForTimingExport($race))->download("{$filename}.csv");
    }
}

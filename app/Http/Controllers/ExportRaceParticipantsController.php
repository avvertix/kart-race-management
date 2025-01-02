<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exports\RaceParticipantsExport;
use App\Models\Race;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ExportRaceParticipantsController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request, Race $race)
    {
        $this->authorize('update', $race);

        $filename = Str::slug(config('races.organizer.name').'-'.$race->event_start_at->toDateString().'-'.$race->title);

        return (new RaceParticipantsExport($race))->download("{$filename}.csv");
    }
}

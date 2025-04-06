<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exports\ParticipantBriefingSignatureExport;
use App\Models\Race;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ExportRaceParticipantsForSigningController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request, Race $race)
    {
        $this->authorize('update', $race);

        $filename = Str::slug('signature-'.$race->title.'-'.$race->event_start_at->toDateString());

        return Excel::download(new ParticipantBriefingSignatureExport($race), "{$filename}.xlsx");
    }
}

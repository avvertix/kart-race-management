<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exports\RaceParticipantsForTimingExport;
use App\Models\Race;
use App\Models\Transponder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ExportRaceParticipantsForTimingController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request, Race $race)
    {
        $this->authorize('create', Transponder::class);

        $filename = Str::slug('mylaps-'.config('races.organizer.name').'-'.$race->event_start_at->toDateString().'-'.$race->title);

        $path = (new RaceParticipantsForTimingExport($race))->store("{$filename}.csv", 'local');

        $content = Storage::disk('local')->get($path);

        Storage::disk('local')->delete($path);

        return response()->streamDownload(function () use ($content) {
            echo Str::replace('"', '', $content);
        }, $path);
    }
}

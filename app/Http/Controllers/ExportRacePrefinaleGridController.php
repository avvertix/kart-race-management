<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\BuildRaceHeatGrids;
use App\Exports\RaceGridForTimingExport;
use App\Models\Race;
use App\Models\Transponder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ExportRacePrefinaleGridController extends Controller
{
    public function __invoke(Race $race, BuildRaceHeatGrids $buildGrids)
    {
        $this->authorize('create', Transponder::class);

        $bibs = $buildGrids($race)
            ->flatMap(fn (array $grid) => $grid['prefinale']['grid'] ?? collect())
            ->map(fn (array $row) => $row['result']->bib)
            ->values();

        $filename = Str::slug('mylaps-prefinale-grid-'.config('races.organizer.name').'-'.$race->event_start_at->toDateString().'-'.$race->title);

        $path = (new RaceGridForTimingExport($race, $bibs))->store("{$filename}.csv", 'local');

        $content = Storage::disk('local')->get($path);

        Storage::disk('local')->delete($path);

        return response()->streamDownload(function () use ($content) {
            echo Str::replace('"', '', $content);
        }, $path);
    }
}

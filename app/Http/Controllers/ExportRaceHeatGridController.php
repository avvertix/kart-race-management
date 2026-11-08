<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\BuildRaceHeatGrids;
use App\Exports\RaceGridForTimingExport;
use App\Models\Race;
use App\Models\Transponder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ExportRaceHeatGridController extends Controller
{
    /**
     * Export the drivers of one heat, of every category, in their starting order.
     */
    public function __invoke(Request $request, Race $race, int $heat, BuildRaceHeatGrids $buildGrids)
    {
        $this->authorize('create', Transponder::class);

        $grids = $buildGrids($race, (array) $request->input('heats', []));

        abort_unless($grids->contains(fn (array $grid) => $grid['assignment']->has($heat)), 404);

        $bibs = $grids
            ->flatMap(fn (array $grid) => $grid['assignment']->get($heat, collect()))
            ->map(fn (array $entry) => $entry['result']->bib)
            ->values();

        $filename = Str::slug("mylaps-heat-{$heat}-".config('races.organizer.name').'-'.$race->event_start_at->toDateString().'-'.$race->title);

        $path = (new RaceGridForTimingExport($race, $bibs))->store("{$filename}.csv", 'local');

        $content = Storage::disk('local')->get($path);

        Storage::disk('local')->delete($path);

        return response()->streamDownload(function () use ($content) {
            echo Str::replace('"', '', $content);
        }, $path);
    }
}

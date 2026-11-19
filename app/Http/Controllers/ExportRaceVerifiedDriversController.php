<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exports\VerifiedDriversExport;
use App\Models\Race;
use App\Models\Transponder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportRaceVerifiedDriversController extends Controller
{
    /**
     * Download the race's verified drivers: the confirmed participants with a transponder, for any licence provider.
     */
    public function __invoke(Request $request, Race $race): BinaryFileResponse
    {
        $this->authorize('view', $race);
        $this->authorize('create', Transponder::class);

        $filename = Str::slug('verified-drivers-'.$race->title.'-'.Carbon::parse($race->event_start_at)->toDateString());

        return Excel::download(new VerifiedDriversExport($race), "{$filename}.xlsx");
    }
}

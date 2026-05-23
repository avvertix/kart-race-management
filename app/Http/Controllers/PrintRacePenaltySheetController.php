<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exports\PrintRacePenaltySheet;
use App\Models\Race;
use App\Models\Transponder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PrintRacePenaltySheetController extends Controller
{
    public function __invoke(Request $request, Race $race)
    {
        $this->authorize('view', $race);
        $this->authorize('create', Transponder::class);

        $filename = Str::slug('penalty-sheet-'.$race->event_start_at->toDateString().'-'.$race->title);

        return (new PrintRacePenaltySheet($race, $request->input('groups', [])))->stream("{$filename}.pdf");
    }
}

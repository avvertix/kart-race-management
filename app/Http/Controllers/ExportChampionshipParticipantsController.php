<?php

namespace App\Http\Controllers;

use App\Exports\ChampionshipParticipantsExport;
use App\Models\Championship;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ExportChampionshipParticipantsController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Championship $championship)
    {
        $this->authorize('update', $championship);

        $filename = Str::slug('participants-'.$championship->title);

        return (new ChampionshipParticipantsExport($championship))->download("{$filename}.xlsx");
    }
}

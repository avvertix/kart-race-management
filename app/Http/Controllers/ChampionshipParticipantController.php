<?php

namespace App\Http\Controllers;

use App\Models\Championship;
use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChampionshipParticipantController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Championship $championship)
    {

        // TODO: performance check
        $subQuery = Participant::where('championship_id', $championship->getKey())
            // ->groupBy('first_name', 'last_name')
            ->groupBy('driver_licence')
            ->orderBy('created_at', 'asc')
            ->select(DB::raw('MAX(id) as identifier'));

        $participants = Participant::query()
            ->whereIn('id', $subQuery)
            ->orderBy('bib', 'asc')
            ->with(['participationHistory', 'participationHistory.race'])
            ->get();
        
        $uniqueParticipantsCount = $participants->count();

        return view('championship.participant.index', [
            'championship' => $championship,
            'participants' => $participants,
            'uniqueParticipantsCount' => $uniqueParticipantsCount,
        ]);
    }
}

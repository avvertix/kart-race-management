<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Championship;
use App\Models\Participant;
use Illuminate\Support\Facades\DB;

class ChampionshipParticipantController extends Controller
{
    /**
     * Create the controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->authorizeResource(Championship::class, 'championship');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Championship $championship)
    {

        $subQuery = Participant::where('championship_id', $championship->getKey())
            // ->groupBy('first_name', 'last_name')
            ->groupBy('driver_licence')
            ->orderBy('created_at', 'asc')
            ->select(DB::raw('MAX(id) as identifier'));

        $participants = Participant::query()
            ->whereIn('id', $subQuery)
            ->orderBy('bib', 'asc')
            ->withOnly([
                'participationHistory' => function ($query) use ($championship) {
                    $query->where('championship_id', $championship->getKey());
                },
                'participationHistory.race',
            ])
            ->get();

        $uniqueParticipantsCount = $participants->count();

        return view('championship.participant.index', [
            'championship' => $championship,
            'participants' => $participants,
            'uniqueParticipantsCount' => $uniqueParticipantsCount,
        ]);
    }
}

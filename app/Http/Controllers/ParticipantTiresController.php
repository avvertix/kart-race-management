<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use App\Models\Tire;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ParticipantTiresController extends Controller
{
    /**
     * Create the controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->authorizeResource(Tire::class, 'tire');
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \App\Models\Participant  $participant
     * @return \Illuminate\Http\Response
     */
    public function index(Participant $participant)
    {
        $participant->load(['race', 'tires']);

        return view('tire.index', [
            'participant' => $participant,
            'race' => $participant->race,
            'tires' => $participant->tires,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param  \App\Models\Participant  $participant
     * @return \Illuminate\Http\Response
     */
    public function create(Participant $participant)
    {
        return view('tire.create', [
            'participant' => $participant,
            'race' => $participant->race,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Participant  $participant
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Participant $participant)
    {        
        $validated = $this->validate($request, [
            'tires' => 'required|array|size:4',
            'tires.*' => 'required|string',
        ]);

        if($participant->tires()->count() >= 4){
            throw ValidationException::withMessages([
                'tires' => __('Participant already have 4 tires assigned'),
            ]);
        }

        $participant->tires()->createMany(collect($validated['tires'])->map(function($tire) use ($participant) {
            return ['code' => $tire, 'race_id' => $participant->race_id];
        }));

        return redirect()->route('participants.tires.index', $participant)->with('flash.banner', __('Tires assigned. Print a copy for the participant.'));
    }

}

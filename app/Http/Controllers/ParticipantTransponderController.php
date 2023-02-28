<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use App\Models\Tire;
use App\Models\Transponder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ParticipantTransponderController extends Controller
{
    /**
     * Create the controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->authorizeResource(Transponder::class, 'transponder');
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \App\Models\Participant  $participant
     * @return \Illuminate\Http\Response
     */
    public function index(Participant $participant)
    {
        $participant->load(['race', 'transponders']);

        return view('transponder.index', [
            'participant' => $participant,
            'race' => $participant->race,
            'transponders' => $participant->transponders,
            'transponderLimit' => $participant->transponders->count() == 0 ? 1 : (2 - $participant->transponders->count()),
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
        $participant->loadCount('transponders');

        return view('transponder.create', [
            'participant' => $participant,
            'race' => $participant->race,
            'transponderLimit' => $participant->transponders_count == 0 ? 1 : (2 - $participant->transponders_count),
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
            'transponders' => 'required|array|min:1|max:2',
            'transponders.*' => [
                'required',
                'string',
                Rule::unique('transponders', 'code')
                    ->where(fn ($query) => $query->where('race_id', $participant->race_id)),
            ],
        ]);

        if($participant->transponders()->count() >= 2){
            throw ValidationException::withMessages([
                'transponders' => __('Participant already have 2 transponders assigned'),
            ]);
        }

        $participant->transponders()->createMany(collect($validated['transponders'])->map(function($transponder) use ($participant) {
            return ['code' => $transponder, 'race_id' => $participant->race_id];
        }));

        return redirect()->route('races.participants.index', $participant->race)->with('flash.banner', __('Transponders assigned.'));
    }

}

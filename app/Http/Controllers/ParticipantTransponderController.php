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

        $transponders = collect($validated['transponders'])->map(function($transponder) use ($participant) {
            return ['code' => $transponder, 'race_id' => $participant->race_id];
        });

        $participant->transponders()->createMany($transponders);

        return redirect()
            ->route('races.participants.index', $participant->race)
            ->with('flash.banner', __('Transponder :number assigned to :participant.', [
                'number' => $transponders->pluck('code')->join(', '),
                'participant' => "{$participant->bib} {$participant->first_name} {$participant->last_name}",
            ]));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Transponder  $transponder
     * @return \Illuminate\Http\Response
     */
    public function edit(Transponder $transponder)
    {
        $transponder->load(['participant', 'participant.race', 'participant.championship']);

        return view('transponder.edit', [
            'participant' => $transponder->participant,
            'race' => $transponder->participant->race,
            'transponderLimit' => 1,
            'transponder' => $transponder,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Transponder  $transponder
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Transponder $transponder)
    {
        $transponder->load(['participant', 'participant.race']);

        $validated = $this->validate($request, [
            'transponder' => [
                'required',
                'integer',
                'min:0',
                Rule::unique('transponders', 'code')
                    ->ignore($transponder)
                    ->where(fn ($query) => $query->where('race_id', $transponder->race_id)),
            ],
        ]);

        $transponder->code = $validated['transponder'];

        $transponder->save();

        return redirect()
            ->route('races.participants.index', $transponder->participant->race)
            ->with('flash.banner', __('Transponder :number assigned to :participant.', [
                'number' => $transponder->code,
                'participant' => "{$transponder->participant->bib} {$transponder->participant->first_name} {$transponder->participant->last_name}",
            ]));
    }

}

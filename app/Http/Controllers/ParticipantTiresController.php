<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Participant;
use App\Models\Tire;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
     * @return \Illuminate\Http\Response
     */
    public function index(Participant $participant)
    {
        $participant->load(['race', 'tires', 'racingCategory.tire']);

        return view('tire.index', [
            'participant' => $participant,
            'race' => $participant->race,
            'tires' => $participant->tires,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Participant $participant)
    {
        $participant->loadCount('tires')->load(['race', 'tires', 'racingCategory.tire']);

        return view('tire.create', [
            'participant' => $participant,
            'race' => $participant->race,
            'tireLimit' => $participant->tires_count === 0 ? 4 : (5 - $participant->tires_count),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Participant $participant)
    {
        $validated = $this->validate($request, [
            'tires' => ['required', 'array', 'min:1', 'max:5'],
            'tires.*' => [
                'required',
                'string',
                Rule::unique('tires', 'code')
                    ->where(fn ($query) => $query->where('race_id', $participant->race_id)),
            ],
        ]);

        if ($participant->tires()->count() >= 5) {
            throw ValidationException::withMessages([
                'tires' => __('Participant already have 5 tires assigned'),
            ]);
        }

        $participant->tires()->createMany(collect($validated['tires'])->map(function ($tire) use ($participant) {
            return ['code' => $tire, 'race_id' => $participant->race_id];
        }));

        return redirect()
            ->route('participants.tires.index', $participant)
            ->with('flash.banner', __('Tires assigned. Print a copy for the participant.'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param  Participant  $participant
     * @return \Illuminate\Http\Response
     */
    public function edit(Tire $tire)
    {
        $tire->load(['participant', 'race', 'participant.racingCategory.tire']);

        return view('tire.edit', [
            'participant' => $tire->participant,
            'race' => $tire->race,
            'tire' => $tire,
        ]);
    }

    public function update(Request $request, Tire $tire)
    {
        $validated = $this->validate($request, [
            'tire' => [
                'required',
                'string',

                Rule::unique('tires', 'code')
                    ->ignore($tire)
                    ->where(fn ($query) => $query->where('race_id', $tire->race_id)),
            ],
        ]);

        $tire->code = $validated['tire'];

        $tire->save();

        return redirect()
            ->route('participants.tires.index', $tire->participant)
            ->with('flash.banner', __('Tire code updated.'));
    }
}

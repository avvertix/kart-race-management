<?php

namespace App\Http\Controllers;

use App\Actions\RegisterParticipant;
use App\Actions\UpdateParticipantRegistration;
use App\Models\Participant;
use App\Models\Race;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RaceParticipantController extends Controller
{
    /**
     * Create the controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->authorizeResource(Participant::class, 'participant');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Race $race)
    {
        $race->load(['championship']);

        return view('participant.index', [
            'race' => $race,
            'championship' => $race->championship,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function create(Race $race, Request $request)
    {

        $templateParticipant = null;

        $race
            ->load([
                'championship',
                'championship.tires',
            ])
            ->loadCount('participants');

        try {
            $validated = $this->validate($request, [
                'from' => [
                    'sometimes', 
                    'nullable', 
                    'string', 
                    Rule::exists('participants', 'uuid')->where(function ($query) use ($race) {
                        return $query->where('championship_id', $race->championship_id);
                    }),
                ]
            ]);

            $templateParticipant = ($validated['from'] ?? false) ? Participant::whereUuid($validated['from'])->first() : null;

        } catch (ValidationException $th) {

        }


        return view('participant.create', [
            'race' => $race,
            'categories' => $race->championship->categories()->enabled()->get(),
            'participant' => $templateParticipant,
            'tires' => $race->championship->tires,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Race $race, Request $request, RegisterParticipant $registerParticipant)
    {

        $participant = $registerParticipant($race, $request->all(), $request->user());
        
        return to_route('races.participants.index', $race)
            ->with('flash.banner', __(':participant added.', [
                'participant' => "{$participant->bib} {$participant->first_name} {$participant->last_name}" 
            ]));
    }

    protected function processAddressInput($input, $fieldPrefix)
    {
        return [
            'address' => $input[$fieldPrefix.'_address'],
            'city' => $input[$fieldPrefix.'_city'],
            'province' => $input[$fieldPrefix.'_province'],
            'postal_code' => $input[$fieldPrefix.'_postal_code'],
        ];
    }

    protected function processVehicle($input)
    {
        return [[
            'chassis_manufacturer' => $input['vehicle_chassis_manufacturer'],
            'engine_manufacturer' => $input['vehicle_engine_manufacturer'],
            'engine_model' => $input['vehicle_engine_model'],
            'oil_manufacturer' => $input['vehicle_oil_manufacturer'],
            'oil_type' => $input['vehicle_oil_type'],
            'oil_percentage' => $input['vehicle_oil_percentage'],
        ]];
    }
    
    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Participant  $participant
     * @return \Illuminate\Http\Response
     */
    public function show(Participant $participant)
    {

        $participant->load(['race', 'championship']);

        return view('participant.show', [
            'race' => $participant,
            'championship' => $participant->championship,
            'participant' => $participant,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Participant  $participant
     * @return \Illuminate\Http\Response
     */
    public function edit(Participant $participant)
    {
        $participant->load(['race', 'championship']);

        return view('participant.edit', [
            'race' => $participant,
            'championship' => $participant->championship,
            'participant' => $participant,
            'categories' => $participant->championship->categories()->enabled()->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Participant  $participant
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Participant $participant, UpdateParticipantRegistration $updateRegistration)
    {

        $updatedParticipant = $updateRegistration($participant->race, $participant, $request->all(), $request->user());
        
        return to_route('races.participants.index', $updatedParticipant->race)
            ->with('flash.banner', __(':participant updated.', [
                'participant' => "{$updatedParticipant->bib} {$updatedParticipant->first_name} {$updatedParticipant->last_name}" 
            ]));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Participant  $participant
     * @return \Illuminate\Http\Response
     */
    public function destroy(Participant $participant)
    {
        //
    }
}

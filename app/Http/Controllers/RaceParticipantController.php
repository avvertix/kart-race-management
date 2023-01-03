<?php

namespace App\Http\Controllers;

use App\Models\LicenceType;
use App\Models\Participant;
use App\Models\Race;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class RaceParticipantController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Race $race, Request $request)
    {

        $validated = $request->validate([
            'bib' => ['required', 'integer', 'min:1', Rule::unique('participants', 'bib')->where(fn ($query) => $query->where('championship_id', $race->championship_id)),],
            'category' => ['required', 'string', ], // TODO: must be within the acceptable categories
            'driver_name' => ['required', 'string', 'max:250'],
            'driver_surname' => ['required', 'string', 'max:250'],
            'driver_licence_number' => ['required', 'string', 'max:250'],
            'driver_licence_type' => ['required', new Enum(LicenceType::class)],
            'driver_licence_renewed_at' => ['nullable'],
            'driver_nationality' => ['required', 'string', 'max:250'],
            'driver_email' => ['required', 'string', 'email'],
            'driver_phone' => ['required', 'string', ],
            'driver_birth_date' => ['required', 'string', ],
            'driver_birth_place' => ['required', 'string', ],
            'driver_medical_certificate_expiration_date' => ['required', 'string', ],
            'driver_residence' => [ 'required', 'array:address,city,province,postal_code' ],
            'driver_residence.address' => ['required', 'string', 'max:250'],
            'driver_residence.city' => ['required', 'string',  'max:250'],
            'driver_residence.province' => ['nullable', 'string',  'max:250'],
            'driver_residence.postal_code' => ['required', 'string', 'max:250'],

            'competitor_licence_number' => ['nullable', 'string', 'max:250'],
            'competitor_licence_type' => ['required_with:competitor_licence_number', 'integer', new Enum(LicenceType::class)],
            'competitor_name' => ['required_with:competitor_licence_number', 'string', 'max:250'],
            'competitor_surname' => ['required_with:competitor_licence_number', 'string', 'max:250'],
            'competitor_licence_renewed_at' => ['nullable'],
            'competitor_nationality' => ['required_with:competitor_licence_number', 'string', 'max:250'],
            'competitor_email' => ['required_with:competitor_licence_number', 'string', 'max:250'],
            'competitor_phone' => ['required_with:competitor_licence_number', 'string', 'max:250'],
            'competitor_birth_date' => ['required_with:competitor_licence_number'],
            'competitor_birth_place' => ['required_with:competitor_licence_number'],
            'competitor_residence' => ['required_with:competitor_licence_number', 'array:address,city,province,postal_code'],
            'competitor_residence.address' => ['required', 'string', 'max:250'],
            'competitor_residence.city' => ['required', 'string',  'max:250'],
            'competitor_residence.province' => ['nullable', 'string',  'max:250'],
            'competitor_residence.postal_code' => ['required', 'string', 'max:250'],
            
            'mechanic_licence_number' => ['nullable', 'string', 'max:250'],
            'mechanic_licence_type' => ['required_with:mechanic_licence_number', 'integer', new Enum(LicenceType::class)],
            'mechanic_name' => ['required_with:mechanic_licence_number', 'string', 'max:250'],
            'mechanic_surname' => ['required_with:mechanic_licence_number', 'string', 'max:250'],
            'mechanic_nationality' => ['required_with:mechanic_licence_number', 'string', 'max:250'],

            'consent_privacy' => ['required', 'accepted'],
        ]);

        // TODO: ensure there is a lock on the bib so no one can take it while we validate and insert the records
        // TODO: track consents
        // TODO: track bonus

        $participant = DB::transaction(function() use ($validated, $race, $request){
            
            $participant = $race->participants()->create([
                'bib' => $validated['bib'],
                'category' => $validated['category'],
                'name' => $validated['driver_name'],
                'surname' => $validated['driver_surname'],
                'added_by' => $request->user()?->getKey(),
                'championship_id' => $race->championship_id,
            ]);
            
            $participant->drivers()->create([
                'name' => $validated['driver_name'],
                'surname' => $validated['driver_surname'],
                'licence_number' => $validated['driver_licence_number'],
                'licence_type' => $validated['driver_licence_type'],
                'licence_renewed_at' => $validated['driver_licence_renewed_at'],
                'nationality' => $validated['driver_nationality'],
                'email' => $validated['driver_email'],
                'phone' => $validated['driver_phone'],
                'birth_date' => $validated['driver_birth_date'],
                'birth_place' => $validated['driver_birth_place'],
                'medical_certificate_expiration_date' => $validated['driver_medical_certificate_expiration_date'],
                'residence_address' => "{$validated['driver_residence']['address']} {$validated['driver_residence']['city']} {$validated['driver_residence']['province']} {$validated['driver_residence']['postal_code']}",
                'sex' => 10, // if the value is encrypted it cannot have null values?
            ]);
            
            $participant->competitors()->create([
                'name' => $validated['competitor_name'],
                'surname' => $validated['competitor_surname'],
                'licence_number' => $validated['competitor_licence_number'],
                'licence_type' => $validated['competitor_licence_type'],
                'licence_renewed_at' => $validated['competitor_licence_renewed_at'],
                'nationality' => $validated['competitor_nationality'],
                'email' => $validated['competitor_email'],
                'phone' => $validated['competitor_phone'],
                'birth_date' => $validated['competitor_birth_date'],
                'birth_place' => $validated['competitor_birth_place'],
                'residence_address' => ($validated['competitor_residence'] ?? false) ? "{$validated['competitor_residence']['address']} {$validated['competitor_residence']['city']} {$validated['competitor_residence']['province']} {$validated['competitor_residence']['postal_code']}" : null,
            ]);
            
            $participant->mechanics()->create([
                'name' => $validated['mechanic_name'],
                'surname' => $validated['mechanic_surname'],
                'licence_number' => $validated['mechanic_licence_number'],
                'licence_type' => $validated['mechanic_licence_type'],
                'nationality' => $validated['mechanic_nationality'],
            ]);

            return $participant;
        });
        
        return to_route('races.participants.index', $race)
            ->with('message', __(':participant added.', [
                'participant' => "{$participant->bib} {$participant->name} {$participant->surname}" 
            ]));
    }
    
    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Participant  $participant
     * @return \Illuminate\Http\Response
     */
    public function show(Participant $participant)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Participant  $participant
     * @return \Illuminate\Http\Response
     */
    public function edit(Participant $participant)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Participant  $participant
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Participant $participant)
    {
        //
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

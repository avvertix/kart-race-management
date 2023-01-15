<?php

namespace App\Http\Controllers;

use App\Categories\Category;
use App\Models\Competitor;
use App\Models\CompetitorLicence;
use App\Models\Driver;
use App\Models\DriverLicence;
use App\Models\Participant;
use App\Models\Race;
use App\Models\Sex;
use App\Rules\ExistsCategory;
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
    public function create(Race $race)
    {
        return view('participant.create', [
            'race' => $race,
            'categories' => Category::all(),
        ]);
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
            'bib' => ['required', 'integer', 'min:1', 
                // Rule::unique('drivers', 'bib')->where(fn ($query) => $query->where('championship_id', $race->championship_id)),
                Rule::unique('participants', 'bib')->where(fn ($query) => $query->where('race_id', $race->getKey())),],
            'category' => ['required', 'string', new ExistsCategory],
            'driver_licence_type' => ['required', new Enum(DriverLicence::class)],
            
            'driver_first_name' => ['required', 'string', 'max:250'],
            'driver_last_name' => ['required', 'string', 'max:250'],
            
            'driver_licence_number' => ['required', 'string', 'max:250'],
            'driver_licence_renewed_at' => ['nullable'],
            'driver_nationality' => ['required', 'string', 'max:250'],
            'driver_email' => ['required', 'string', 'email'],
            'driver_phone' => ['required', 'string', ],
            'driver_birth_date' => ['required', 'string', ],
            'driver_birth_place' => ['required', 'string', ],
            'driver_medical_certificate_expiration_date' => ['required', 'string', ],
            'driver_residence_address' => [ 'required', 'string' ],
            'driver_sex' => [ 'required', new Enum(Sex::class) ],

            'driver_residence_address' => ['required', 'string', 'max:250'],
            'driver_residence_city' => ['required', 'string',  'max:250'],
            'driver_residence_province' => ['nullable', 'string',  'max:250'],
            'driver_residence_postal_code' => ['required', 'string', 'max:250'],
            
            'competitor_licence_number' => ['required', 'string', 'max:250'],
            'competitor_licence_type' => ['required_with:competitor_licence_number', new Enum(CompetitorLicence::class)],
            
            'competitor_first_name' => ['required_with:competitor_licence_number', 'string', 'max:250'],
            'competitor_last_name' => ['required_with:competitor_licence_number', 'string', 'max:250'],

            'competitor_licence_renewed_at' => ['nullable'],
            'competitor_nationality' => ['required_with:competitor_licence_number', 'string', 'max:250'],
            'competitor_email' => ['required_with:competitor_licence_number', 'string', 'email'],
            'competitor_phone' => ['required_with:competitor_licence_number', 'string', ],
            'competitor_birth_date' => ['required_with:competitor_licence_number', 'string', ],
            'competitor_birth_place' => ['required_with:competitor_licence_number', 'string', ],
            'competitor_residence_address' => [ 'required_with:competitor_licence_number', 'string' ],

            'competitor_residence_address' => ['required_with:competitor_licence_number', 'string', 'max:250'],
            'competitor_residence_city' => ['required_with:competitor_licence_number', 'string',  'max:250'],
            'competitor_residence_province' => ['nullable', 'string',  'max:250'],
            'competitor_residence_postal_code' => ['required_with:competitor_licence_number', 'string', 'max:250'],
            
            'mechanic_licence_number' => ['nullable', 'string', 'max:250'],
            'mechanic_name' => ['required_with:mechanic_licence_number', 'string', 'max:250'],

            'vehicles' => [ 'required', 'array', 'min:1', 'max:2' ],
            'vehicles.*.chassis_manufacturer' => ['required', 'string', 'max:250'],
            'vehicles.*.engine_manufacturer' => ['required', 'string',  'max:250'],
            'vehicles.*.engine_model' => ['required', 'string',  'max:250'],
            'vehicles.*.oil_manufacturer' => ['required', 'string', 'max:250'],
            'vehicles.*.oil_type' => ['nullable', 'string',  'max:250'],
            'vehicles.*.oil_percentage' => ['required', 'string', 'max:250'],

            'consent_privacy' => ['required', 'accepted'],
        ]);

        // TODO: ensure there is a lock on the bib so no one can take it while we validate and insert the records
        // TODO: track consents
        // TODO: track bonus

        $participant = DB::transaction(function() use ($validated, $race, $request){

            $participant = $race->participants()->create([
                'bib' => $validated['bib'],
                'category' => $validated['category'],
                'first_name' => $validated['driver_first_name'],
                'last_name' => $validated['driver_last_name'],
                'added_by' => $request->user()?->getKey(),
                'championship_id' => $race->championship_id,
                'driver_licence' => hash('sha512', $validated['driver_licence_number']),
                'competitor_licence' => $validated['competitor_licence_number'] ? hash('sha512', $validated['competitor_licence_number']) : null,
                'driver' => [
                    'first_name' => $validated['driver_first_name'],
                    'last_name' => $validated['driver_last_name'],
                    'licence_type' => $validated['driver_licence_type'],
                    'licence_number' => $validated['driver_licence_number'],
                    'licence_renewed_at' => $validated['driver_licence_renewed_at'],
                    'nationality' => $validated['driver_nationality'],
                    'email' => $validated['driver_email'],
                    'phone' => $validated['driver_phone'],
                    'birth_date' => $validated['driver_birth_date'],
                    'birth_place' => $validated['driver_birth_place'],
                    'medical_certificate_expiration_date' => $validated['driver_medical_certificate_expiration_date'],
                    'residence_address' =>  $this->processAddressInput($validated, 'driver_residence'),
                    'sex' => $validated['driver_sex'],
                ],
                'competitor' => [
                    'first_name' => $validated['competitor_first_name'],
                    'last_name' => $validated['competitor_last_name'],
                    'licence_type' => $validated['competitor_licence_type'],
                    'licence_number' => $validated['competitor_licence_number'],
                    'licence_renewed_at' => $validated['competitor_licence_renewed_at'],
                    'nationality' => $validated['competitor_nationality'],
                    'email' => $validated['competitor_email'],
                    'phone' => $validated['competitor_phone'],
                    'birth_date' => $validated['competitor_birth_date'],
                    'birth_place' => $validated['competitor_birth_place'],
                    'residence_address' => $this->processAddressInput($validated, 'competitor_residence'),
                ],
                'mechanic' => [
                    'name' => $validated['mechanic_name'],
                    'licence_number' => $validated['mechanic_licence_number'],
                ],
                'vehicles' => $validated['vehicles'],
            ]);
            
            return $participant;
        });
        
        return to_route('races.participants.index', $race)
            ->with('message', __(':participant added.', [
                'participant' => "{$participant->bib} {$participant->first_name} {$participant->last_name}" 
            ]));
    }

    protected function processAddressInput($input, $fieldPrefix)
    {
        return collect([
            $input[$fieldPrefix.'_address'],
            $input[$fieldPrefix.'_city'],
            $input[$fieldPrefix.'_province'],
            $input[$fieldPrefix.'_postal_code'],
        ])->join(' ');
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

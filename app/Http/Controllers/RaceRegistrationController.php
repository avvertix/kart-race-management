<?php

namespace App\Http\Controllers;

use App\Actions\RegisterParticipant;
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

class RaceRegistrationController extends Controller
{

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Race $race)
    {
        $race->load('championship');

        return view('race-registration.create', [
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
    public function store(Race $race, Request $request, RegisterParticipant $registerParticipant)
    {

        $participant = $registerParticipant($race, $request->all());
        
        return to_route('registration.show', $participant)
            // TODO: maybe add a signature
            ->with('message', __('Race registration recorded.'));
    }
    
    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Participant  $participant
     * @return \Illuminate\Http\Response
     */
    public function show(Participant $registration)
    {
        $registration->load(['race', 'championship']);

        return view('race-registration.show', [
            'race' => $registration->race,
            'championship' => $registration->championship,
            'participant' => $registration,
        ]);
    }

}

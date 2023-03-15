<?php

namespace App\Http\Controllers;

use App\Actions\RegisterParticipant;
use App\Categories\Category;
use App\Exceptions\InvalidParticipantSignatureException;
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
use Illuminate\Support\Facades\URL;
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
            'registration_open' => $race->isRegistrationOpen,
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

        if(!$race->isRegistrationOpen){
            return redirect()
                ->route('races.registration.create', $race)
                ->with('flash', [
                    'banner' => __('Online registration closed. Registration might still be possible at the race track.'),
                    'bannerStyle' => 'danger',
                ]);
        }

        

        $participant = $registerParticipant($race, $request->all());

        return redirect()
            ->signedRoute(
                'registration.show',
                ['registration' => $participant, 'p' => $participant->signatureContent()]
            )
            ->with('flash.banner', __('Race registration recorded. Please confirm it using the link sent in the email.'));
    }
    
    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Participant  $participant
     * @return \Illuminate\Http\Response
     */
    public function show(Participant $registration, Request $request)
    {
        throw_unless($request->hasValidSignature(), InvalidParticipantSignatureException::class);

        $registration
            ->load(['race', 'championship', 'signatures']);

        return view('race-registration.show', [
            'race' => $registration->race,
            'championship' => $registration->championship,
            'participant' => $registration,
        ]);
    }

}

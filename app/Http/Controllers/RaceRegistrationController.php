<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\RegisterParticipant;
use App\Exceptions\InvalidParticipantSignatureException;
use App\Models\Participant;
use App\Models\Race;
use Illuminate\Http\Request;

class RaceRegistrationController extends Controller
{
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Race $race)
    {
        $race
            ->load([
                'championship',
                'championship.tires',
            ])
            ->loadCount('participants');

        return view('race-registration.create', [
            'race' => $race,
            'categories' => $race->championship->categories()->enabled()->get(),
            'registration_open' => $race->isRegistrationOpen,
            'tires' => $race->championship->tires,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Race $race, Request $request, RegisterParticipant $registerParticipant)
    {

        if (! $race->isRegistrationOpen) {
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
     * @param  Participant  $participant
     * @return \Illuminate\Http\Response
     */
    public function show(Participant $registration, Request $request)
    {
        throw_unless($request->hasValidSignature(), InvalidParticipantSignatureException::class);

        $registration
            ->load(['race', 'championship', 'signatures', 'payments']);

        return view('race-registration.show', [
            'race' => $registration->race,
            'championship' => $registration->championship,
            'participant' => $registration,
        ]);
    }
}

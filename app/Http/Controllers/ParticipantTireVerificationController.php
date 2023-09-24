<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidParticipantTiresSignatureException;
use App\Models\Participant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Fortify;

class ParticipantTireVerificationController extends Controller
{
    /**
     * Send a new email verification notification.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {

        throw_unless($request->hasValidSignature(), new InvalidParticipantTiresSignatureException());

        $participant = Participant::whereUuid($request->registration)->first();

        throw_if(is_null($participant), new InvalidParticipantTiresSignatureException());
        
        throw_if(md5($participant->uuid) !== $request->input('p'), new InvalidParticipantTiresSignatureException());

        $participant->load(['race', 'tires']);

        return view('tire.index', [
            'participant' => $participant,
            'race' => $participant->race,
            'tires' => $participant->tires,
        ]);
    }
}

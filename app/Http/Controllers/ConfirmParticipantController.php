<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use Illuminate\Http\Request;

class ConfirmParticipantController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {

        $validated = $this->validate($request, [
            'p' => ['required', 'exists:participants,uuid'],
            't' => ['required', 'in:driver,competitor'],
            'hash' => ['required'], // sha1($this->getEmailForVerification($target))
        ]);

        $participant = Participant::with('race')
            ->whereUuid($validated['p'])
            ->first();

        // if the signature already exists the user clicked two times within the expiration of the link

        if($participant->signatures()->where('signature', $validated['hash'])->exists()){
            return redirect($participant->qrCodeUrl());
        }

        $signature = $participant->signatures()->create([
            'signature' => $validated['hash'],
            'signed_at' => now(),
        ]);

        return redirect($participant->qrCodeUrl())
            ->with('message', __('You signed the participation request.'));

    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Participant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Fortify;

class ParticipantSignatureNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        if (! $request->hasValidSignature()) {
            abort(401, __('We cannot verify your identity, please follow the URL in the email.'));
        }

        $participant = Participant::whereUuid($request->participant)->first();

        if ($participant->hasSignedTheRequest()) {
            return $request->wantsJson()
                    ? new JsonResponse('', 204)
                    : redirect($participant->qrCodeUrl());
        }

        $participant->sendConfirmParticipantNotification();

        return $request->wantsJson()
                    ? new JsonResponse('', 202)
                    : back()->with('status', Fortify::VERIFICATION_LINK_SENT);
    }
}

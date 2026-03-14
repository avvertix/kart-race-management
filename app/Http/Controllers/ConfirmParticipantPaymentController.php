<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Participant;
use Illuminate\Http\Request;

class ConfirmParticipantPaymentController extends Controller
{
    public function __invoke(Request $request, Participant $participant): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $participant);

        $participant->update([
            'payment_confirmed_at' => $participant->payment_confirmed_at ? null : now(),
        ]);

        return back();
    }
}

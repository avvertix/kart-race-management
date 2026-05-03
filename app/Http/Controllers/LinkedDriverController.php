<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Participant;
use Illuminate\Http\Request;

class LinkedDriverController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('drivers:view');

        $user = $request->user();

        $linkedParticipants = Participant::registered()
            ->where(function ($q) use ($user) {
                $q->where('claimed_by', $user->id)
                    ->orWhere('added_by', $user->id);
            })
            ->with('race.championship')
            ->latest()
            ->get()
            ->unique('driver_licence');

        return view('linked-driver.index', [
            'linkedParticipants' => $linkedParticipants,
        ]);
    }
}

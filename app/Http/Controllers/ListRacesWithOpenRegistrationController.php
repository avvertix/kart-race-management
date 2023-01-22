<?php

namespace App\Http\Controllers;

use App\Models\Race;
use Illuminate\Http\Request;

class ListRacesWithOpenRegistrationController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {

        $races = Race::query()
            ->withRegistrationOpen()
            ->orderBy('event_start_at')
            ->with('championship')
            ->get();

        return view('welcome', [
            'races' => $races,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Race;
use Illuminate\Database\Eloquent\Builder;
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
            ->visible()
            ->orWhere(function (Builder $query) {
                $query->active();
            })
            ->orWhere(function (Builder $query) {
                $query->where('event_start_at', '<=', now()->subDays(2))
                      ->where('event_start_at', '>=', now());
            })
            ->orderBy('event_start_at')
            ->with('championship')
            ->get();

        return view('welcome', [
            'races' => $races,
        ]);
    }
}

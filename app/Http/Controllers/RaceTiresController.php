<?php

namespace App\Http\Controllers;

use App\Models\Race;
use App\Models\Tire;
use App\Models\TireOption;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class RaceTiresController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Race  $race
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request, Race $race)
    {
        $this->authorize('viewAny', Tire::class);

        $race->load(['championship']);

        $tires = $race->participants()
            ->selectRaw('category, count(*) as total')
            ->groupBy('category')
            ->get()
            ->map(function($p){
                return [
                    'category' => $p->category,
                    'total' => $p->total,
                    'tire' => $p->category()->tires,
                ];
            })
            ->mapToGroups(function($p){

                return [ $p['tire'] => $p];
            })
            ->mapWithKeys(function($p, $tire){
                return [ 
                    $tire => [
                        'name' => TireOption::find($tire)->name,
                        'total' => collect($p)->sum('total'),
                        'raw' => $p,
                    ]
                ];
            })
            ->values();

        // List participants with tires

        $participants = $race->participants()
            ->withCount('tires')
            ->has('tires')
            ->orderBy('bib', 'asc')
            ->get();

        return view('race.tires', [
            'race' => $race,
            'championship' => $race->championship,
            'tires' => $tires,
            'participants' => $participants,
        ]);
    }
    
}

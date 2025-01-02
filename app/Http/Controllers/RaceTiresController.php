<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Race;
use App\Models\Tire;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class RaceTiresController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request, Race $race)
    {
        $this->authorize('viewAny', Tire::class);

        $race->load(['championship']);

        $tires = $race->championship
            ->tires()
            ->withCount([
                'participants' => function (Builder $query) use ($race) {
                    $query->where('race_id', $race->getKey());
                },
                'participants as participants_with_tires' => function (Builder $query) use ($race) {
                    $query->where('race_id', $race->getKey())->has('tires');
                },
            ])
            ->get()
            ->map(function ($p) {
                return [
                    'name' => $p->name,
                    'total' => $p->participants_count,
                    'assigned' => $p->participants_with_tires,
                    'raw' => $p,
                ];
            })
            ->filter(fn ($e) => $e['total'] > 0);

        $search_term = $request->has('tire_search') ? e($request->get('tire_search')) : null;

        // List participants with tires

        $participants = $race->participants()
            ->withCount('tires')
            ->withCount('signatures')
            ->has('tires')
            ->when($search_term, function ($query, $search_term) {
                $query->search($search_term)
                    ->orWhereRelation('tires', 'code', e($search_term));
            })
            ->orderBy('bib', 'asc')
            ->get();

        return view('race.tires', [
            'race' => $race,
            'championship' => $race->championship,
            'tires' => $tires,
            'participants' => $participants,
            'search_term' => $search_term,
        ]);
    }
}

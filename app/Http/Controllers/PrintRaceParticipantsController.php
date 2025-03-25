<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Race;
use Illuminate\Http\Request;
use Illuminate\Support\Fluent;

class PrintRaceParticipantsController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request, Race $race)
    {
        $race->load(['championship']);

        $validated = new Fluent($request->validate([
            'sort' => 'nullable|in:bib,registration',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]));

        $participants = $race->participants()
            ->withCount('tires')
            ->when($validated->get('sort') === 'registration', function ($query) {
                $query->orderBy('created_at', 'ASC');
            }, function ($query) {
                $query->orderBy('bib');
            })
            ->when($validated->filled('from'), function ($query, $registrationDateFrom) use ($validated) {
                $query->where('created_at', '>=', $validated->date('from')->startOfDay());
            })
            ->when($validated->filled('to'), function ($query, $registrationDateTo) use ($validated) {
                $query->where('created_at', '<=', $validated->date('to')->endOfDay());
            })
            ->get();

        return view('participant.print', [
            'race' => $race,
            'championship' => $race->championship,
            'participants' => $participants,
            'sort' => $validated->get('sort'),
            'from' => $validated->get('from'),
            'to' => $validated->get('to'),
        ]);
    }
}

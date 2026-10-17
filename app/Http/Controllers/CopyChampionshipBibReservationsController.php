<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CopyChampionshipBibReservations;
use App\Models\BibReservation;
use App\Models\Championship;
use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CopyChampionshipBibReservationsController extends Controller
{
    /**
     * Show the form for copying race number reservations from another championship.
     */
    public function create(Championship $championship)
    {
        $this->authorize('create', BibReservation::class);

        $sourceChampionships = Championship::query()
            ->where('id', '!=', $championship->id)
            ->where(fn ($query) => $query
                ->whereHas('reservations')
                ->orWhereIn('id', Participant::query()->select('championship_id')))
            ->orderBy('title', 'ASC')
            ->withCount('reservations')
            ->get();

        return view('bib-reservation.copy', [
            'championship' => $championship,
            'sourceChampionships' => $sourceChampionships,
        ]);
    }

    /**
     * Copy race number reservations from another championship.
     */
    public function store(Request $request, Championship $championship, CopyChampionshipBibReservations $copyReservations)
    {
        $this->authorize('create', BibReservation::class);

        $validated = $request->validate([
            'source_championship' => [
                'required',
                'integer',
                Rule::exists((new Championship())->getTable(), 'id'),
                Rule::notIn([$championship->getKey()]),
            ],
            'source' => [
                'required',
                Rule::in([CopyChampionshipBibReservations::FROM_RESERVATIONS, CopyChampionshipBibReservations::FROM_PARTICIPANTS]),
            ],
            'reservation_expiration_date' => 'nullable|date|after:today',
        ]);

        $sourceChampionship = Championship::findOrFail($validated['source_championship']);

        $result = $copyReservations(
            $sourceChampionship,
            $championship,
            $validated['source'],
            $request->date('reservation_expiration_date')?->endOfDay(),
        );

        $message = $result['skipped']->isEmpty()
            ? __(':copied reservations copied.', [
                'copied' => $result['copied']->count(),
            ])
            : __(':copied reservations copied. Race numbers skipped due to conflicts or outside the allowed range: :bibs.', [
                'copied' => $result['copied']->count(),
                'bibs' => $result['skipped']->join(', '),
            ]);

        return redirect()->route('championships.bib-reservations.index', $championship)
            ->with('flash.banner', $message);
    }
}

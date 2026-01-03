<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\BibReservation;
use App\Models\Championship;
use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BibReservationController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(BibReservation::class, 'bib_reservation');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Championship $championship)
    {

        $areThereSomeReservationNotEnforced = $championship->reservations()->withoutLicence()->exists();

        return view('bib-reservation.index', [
            'championship' => $championship,
            'reservations' => $championship->reservations()->get(),
            'areThereSomeReservationNotEnforced' => $areThereSomeReservationNotEnforced,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Championship $championship)
    {
        return view('bib-reservation.create', [
            'championship' => $championship,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Championship $championship, Request $request)
    {
        $validated = $this->validate($request, [
            'bib' => [
                'required',
                'integer',
                'min:1',
                Rule::unique((new BibReservation())->getTable(), 'bib')->where(function ($query) use ($championship) {
                    return $query->where('championship_id', $championship->getKey());
                }),

                Rule::unique('participants', 'bib')
                    ->where(fn ($query) => $query
                        ->where('championship_id', $championship->getKey())),
            ],
            'driver' => [
                'required',
                'string',
                'max:250',
                Rule::unique((new BibReservation())->getTable(), 'driver')->where(function ($query) use ($championship) {
                    return $query->where('championship_id', $championship->getKey());
                }),
            ],

            'driver_licence_number' => ['required', 'string', 'max:250'],

            'contact_email' => 'nullable|string|email',

            'reservation_expiration_date' => 'nullable|date|after:today',
        ]);

        $licenceHash = ($validated['driver_licence_number'] ?? null) ? hash('sha512', $validated['driver_licence_number']) : null;

        // Prevent to use a bib different than what already assigned to the same driver licence

        if (! is_null($licenceHash)) {
            $part = Participant::where('driver_licence', $licenceHash)->where('championship_id', $championship->getKey())->first();

            if ($part && $part->bib !== $validated['bib']) {
                throw ValidationException::withMessages(['bib' => __('Participant with same licence has the race number :bib.', ['bib' => $part->bib])]);
            }
        }

        $reservation = $championship->reservations()->create([
            'bib' => $validated['bib'],
            'driver' => $validated['driver'],
            'contact_email' => $validated['contact_email'],
            'driver_licence_hash' => $licenceHash,
            'driver_licence' => $validated['driver_licence_number'] ?? null,
            'reservation_expires_at' => $request->date('reservation_expiration_date')?->endOfDay(),
        ]);

        return redirect()->route('championships.bib-reservations.index', $championship)
            ->with('flash.banner', __('Race number :bib reserved.', [
                'bib' => $reservation->bib,
            ]));
    }

    /**
     * Display the specified resource.
     */
    public function show(BibReservation $bibReservation)
    {
        return view('bib-reservation.show', [
            'championship' => $bibReservation->championship,
            'reservation' => $bibReservation,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BibReservation $bibReservation)
    {
        return view('bib-reservation.edit', [
            'championship' => $bibReservation->championship,
            'reservation' => $bibReservation,
        ]);

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BibReservation $bibReservation)
    {
        // edit of reservation number can be done until the number is not already used within a race

        $championship = $bibReservation->championship;

        $validated = $this->validate($request, [
            'bib' => [
                'required',
                'integer',
                'min:1',
                // TODO: can I change the bib number?
                Rule::unique((new BibReservation())->getTable(), 'bib')->ignore($bibReservation)->where(function ($query) use ($championship) {
                    return $query->where('championship_id', $championship->getKey());
                }),

            ],
            'driver' => [
                'required',
                'string',
                'max:250',
                Rule::unique((new BibReservation())->getTable(), 'driver')->ignore($bibReservation)->where(function ($query) use ($championship) {
                    return $query->where('championship_id', $championship->getKey());
                }),
            ],

            'driver_licence_number' => ['required', 'string', 'max:250'],

            'contact_email' => 'nullable|string|email',

            'reservation_expiration_date' => 'nullable|date|after:today',
        ]);

        // Prevent to remove licence number once set
        if (empty($validated['driver_licence_number'] ?? null) && ! is_null($bibReservation->driver_licence_hash)) {
            throw ValidationException::withMessages(['driver_licence_number' => __('Removing licence not allowed.')]);
        }

        // Prevent to use a bib different than what already assigned to the same driver licence
        $licenceHash = hash('sha512', $validated['driver_licence_number']);

        $part = Participant::where('driver_licence', $licenceHash)->where('championship_id', $bibReservation->championship_id)->first();

        // since licence matches we probably can let it go

        if ($part && (int) ($part->bib) !== (int) ($validated['bib'])) {
            throw ValidationException::withMessages(['bib' => __('A driver with the same licence is already partecipating in championship with number :bib.', ['bib' => $part->bib])]);
        }

        // check if already assigned to another driver in this championship

        $partByBib = Participant::where('bib', $validated['bib'])->where('championship_id', $bibReservation->championship_id)->first();

        if ($partByBib && $partByBib->driver_licence !== $licenceHash) {
            throw ValidationException::withMessages(['bib' => __('Bib :bib used by another driver (:driver) in championship.', [
                'bib' => $validated['bib'],
                'driver' => $partByBib->fullName,
            ])]);
        }

        $bibReservation->update([
            'bib' => $validated['bib'],
            'driver' => $validated['driver'],
            'contact_email' => $validated['contact_email'],
            'driver_licence_hash' => $licenceHash,
            'driver_licence' => $validated['driver_licence_number'] ?? null,
            'reservation_expires_at' => $request->date('reservation_expiration_date')?->endOfDay(),
        ]);

        return redirect()->route('championships.bib-reservations.index', $championship)
            ->with('flash.banner', __('Reservation for :bib updated.', [
                'bib' => $bibReservation->bib,
            ]));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BibReservation $bibReservation)
    {
        $championship = $bibReservation->championship;

        $bib = $bibReservation->bib;

        $bibReservation->delete();

        return redirect()->route('championships.bib-reservations.index', $championship)
            ->with('flash.banner', __('Reservation for :bib removed.', [
                'bib' => $bib,
            ]));
    }
}

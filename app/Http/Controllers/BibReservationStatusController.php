<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\BibReservationStatus;
use App\Models\BibReservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The organizer moves a BIB Reservation along its lifecycle, see docs/reference/lifecycles.md#bib-reservation.
 */
class BibReservationStatusController extends Controller
{
    /**
     * Confirm a reservation picked by a driver. With `return=enrollment` it goes back to the Enrollment that picked it.
     */
    public function confirm(Request $request, BibReservation $bibReservation): RedirectResponse
    {
        $this->authorize('confirm', $bibReservation);

        $bibReservation->update(['status' => BibReservationStatus::Confirmed]);

        $redirect = $request->input('return') === 'enrollment' && $bibReservation->enrollment !== null
            ? to_route('enrollments.show', $bibReservation->enrollment)
            : to_route('bib-reservations.show', $bibReservation);

        return $redirect
            ->with('flash.banner', __('Reservation for :bib confirmed.', ['bib' => $bibReservation->bib]));
    }

    /**
     * Release the reservation: its number is free for other drivers again.
     */
    public function release(BibReservation $bibReservation): RedirectResponse
    {
        $this->authorize('release', $bibReservation);

        $bibReservation->update([
            'status' => BibReservationStatus::Released,
            'released_at' => now(),
        ]);

        return to_route('bib-reservations.show', $bibReservation)
            ->with('flash.banner', __('Reservation for :bib released.', ['bib' => $bibReservation->bib]));
    }
}

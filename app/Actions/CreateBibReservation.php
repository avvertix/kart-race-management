<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\BibReservationStatus;
use App\Models\BibReservation;
use App\Models\Championship;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreateBibReservation
{
    /**
     * Create a confirmed reservation. Released reservations don't hold their number nor driver.
     *
     * @param  array{bib: int, driver: string, driver_licence_number: string, contact_email: ?string, reservation_expiration_date: ?string}  $input
     */
    public function __invoke(Championship $championship, array $input, ?User $createdBy = null): BibReservation
    {
        Validator::make($input, [
            'bib' => [
                'required',
                'integer',
                'min:'.BibReservation::MIN_BIB,
                'max:'.BibReservation::MAX_BIB,
                BibReservation::uniqueAmongHolding($championship, 'bib'),
                Rule::unique('participants', 'bib')
                    ->where(fn ($query) => $query->where('championship_id', $championship->getKey())),
            ],
            'driver' => [
                'required',
                'string',
                'max:250',
                BibReservation::uniqueAmongHolding($championship, 'driver'),
            ],
            'driver_licence_number' => ['required', 'string', 'max:250'],
            'contact_email' => 'nullable|string|email',
            'reservation_expiration_date' => 'nullable|date|after:today',
        ])->validate();

        $licenceHash = ! empty($input['driver_licence_number'])
            ? hash('sha512', $input['driver_licence_number'])
            : null;

        if (! is_null($licenceHash)) {
            $participant = Participant::where('driver_licence', $licenceHash)
                ->where('championship_id', $championship->getKey())
                ->first();

            if ($participant && $participant->bib !== $input['bib']) {
                throw ValidationException::withMessages([
                    'bib' => __('Participant with same licence has the race number :bib.', ['bib' => $participant->bib]),
                ]);
            }
        }

        $expiresAt = ! empty($input['reservation_expiration_date'])
            ? \Illuminate\Support\Facades\Date::parse($input['reservation_expiration_date'])->endOfDay()
            : null;

        return $championship->reservations()->create([
            'bib' => $input['bib'],
            'driver' => $input['driver'],
            'contact_email' => $input['contact_email'] ?? null,
            'driver_licence_hash' => $licenceHash,
            'driver_licence' => $input['driver_licence_number'] ?? null,
            'reservation_expires_at' => $expiresAt,
            'status' => BibReservationStatus::Confirmed,
            'created_by_user_id' => $createdBy?->getKey(),
        ]);
    }
}

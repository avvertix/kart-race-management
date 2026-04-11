<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\BibReservation;
use App\Models\Championship;
use App\Models\Participant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BibReservationImportController extends Controller
{
    public function create(Championship $championship)
    {
        $this->authorize('create', BibReservation::class);

        return view('bib-reservation.import', [
            'championship' => $championship,
        ]);
    }

    public function store(Request $request, Championship $championship)
    {
        $this->authorize('create', BibReservation::class);

        $validated = $this->validate($request, [
            'reservations' => 'required|string',
        ]);

        $data = collect(Str::of($validated['reservations'])->split('/[\n\r]+/'))->map(function ($line) {
            if (empty(trim($line))) {
                return null;
            }

            $parsedLine = str_getcsv($line, ';');

            return [
                'bib' => $parsedLine[0] ?? null,
                'driver' => $parsedLine[1] ?? null,
                'driver_licence_number' => ! empty($parsedLine[2]) ? $parsedLine[2] : null,
                'contact_email' => ! empty($parsedLine[3]) ? $parsedLine[3] : null,
                'reservation_expiration_date' => ! empty($parsedLine[4]) ? $parsedLine[4] : null,
            ];
        })->filter()->values();

        $validator = Validator::make(
            ['reservations' => $data->toArray()],
            [
                'reservations.*.bib' => 'required|integer|min:1',
                'reservations.*.driver' => 'required|string|max:250',
                'reservations.*.driver_licence_number' => ['required', 'string', 'max:250'],
                'reservations.*.contact_email' => 'nullable|string|email',
                'reservations.*.reservation_expiration_date' => 'nullable|date|after:today',
            ],
            [],
            [
                'reservations.*.bib' => 'bib',
                'reservations.*.driver' => 'driver',
                'reservations.*.driver_licence_number' => 'driver_licence_number',
                'reservations.*.contact_email' => 'contact_email',
                'reservations.*.reservation_expiration_date' => 'reservation_expiration_date',
            ]
        );

        if ($validator->fails()) {
            $errors = $validator->errors();

            $messages = collect($errors->messages())->groupBy(function ($value, $key) {
                return Str::between($key, '.', '.');
            })->sortKeys()->mapWithKeys(function ($values, $line) {
                return [__('Line :line contains invalid data', ['line' => (int) $line + 1]) => $values->flatten()];
            });

            throw ValidationException::withMessages([
                'reservations' => $messages,
            ]);
        }

        $toCreate = collect($validator->validated()['reservations'])->map(function ($d) {
            return [
                'bib' => (int) $d['bib'],
                'driver' => $d['driver'],
                'driver_licence' => $d['driver_licence_number'],
                'driver_licence_hash' => hash('sha512', $d['driver_licence_number']),
                'contact_email' => $d['contact_email'] ?? null,
                'reservation_expires_at' => ! empty($d['reservation_expiration_date'])
                    ? Date::parse($d['reservation_expiration_date'])->endOfDay()
                    : null,
            ];
        });

        $duplicateErrors = [];

        $bibs = $toCreate->pluck('bib');
        $drivers = $toCreate->pluck('driver');
        $licenceHashes = $toCreate->pluck('driver_licence_hash');

        $existingBibsInReservations = $championship->reservations()
            ->whereIn('bib', $bibs->toArray())
            ->pluck('bib');

        $existingBibsInParticipants = Participant::query()
            ->where('championship_id', $championship->getKey())
            ->whereIn('bib', $bibs->toArray())
            ->pluck('bib');

        $existingDrivers = $championship->reservations()
            ->whereIn('driver', $drivers->toArray())
            ->pluck('driver');

        $participantsWithConflictingBib = Participant::query()
            ->where('championship_id', $championship->getKey())
            ->whereIn('driver_licence', $licenceHashes->toArray())
            ->pluck('bib', 'driver_licence');

        foreach ($toCreate as $index => $row) {
            $lineKey = __('Line :line contains invalid data', ['line' => $index + 1]);

            if ($existingBibsInReservations->contains($row['bib'])) {
                $duplicateErrors[$lineKey][] = __('Race number :bib is already reserved.', ['bib' => $row['bib']]);
            }

            if ($existingBibsInParticipants->contains($row['bib'])) {
                $duplicateErrors[$lineKey][] = __('Race number :bib is already assigned to a participant.', ['bib' => $row['bib']]);
            }

            if ($existingDrivers->contains($row['driver'])) {
                $duplicateErrors[$lineKey][] = __('A reservation for driver :driver already exists.', ['driver' => $row['driver']]);
            }

            $conflictingBib = $participantsWithConflictingBib->get($row['driver_licence_hash']);
            if (! is_null($conflictingBib) && (int) $conflictingBib !== $row['bib']) {
                $duplicateErrors[$lineKey][] = __('Participant with same licence has the race number :bib.', ['bib' => $conflictingBib]);
            }
        }

        if (! empty($duplicateErrors)) {
            throw ValidationException::withMessages([
                'reservations' => $duplicateErrors,
            ]);
        }

        $championship->reservations()->createMany($toCreate->toArray());

        return to_route('championships.bib-reservations.index', $championship)
            ->with('flash.banner', __(':count reservations imported.', ['count' => $toCreate->count()]));
    }
}

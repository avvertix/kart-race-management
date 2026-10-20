<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Participant;
use App\Models\ParticipantResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MergeDuplicateDrivers
{
    /**
     * Assign the driver licence of the canonical participant to the duplicates so that all
     * participations are identified as the same driver, e.g. when calculating awards.
     *
     * The canonical participant is realigned too, in case its racer hash was not updated
     * after a licence correction.
     *
     * @param  Collection<int, Participant>  $duplicates
     */
    public function __invoke(Participant $canonical, Collection $duplicates): void
    {
        $duplicates = $duplicates->reject(fn (Participant $participant) => $participant->is($canonical))->values();

        if ($duplicates->isEmpty()) {
            throw ValidationException::withMessages([
                'duplicates' => __('Select at least one participation to merge.'),
            ]);
        }

        if ($duplicates->contains(fn (Participant $participant) => $participant->championship_id !== $canonical->championship_id)) {
            throw ValidationException::withMessages([
                'duplicates' => __('Participations must belong to the same championship.'),
            ]);
        }

        $participations = $duplicates->prepend($canonical);

        if ($participations->pluck('race_id')->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages([
                'duplicates' => __('Participations of the same race cannot be merged as they belong to different drivers.'),
            ]);
        }

        $licenceNumber = $canonical->driver['licence_number'];
        $licenceHash = hash('sha512', $licenceNumber);

        $racerHash = mb_substr($licenceHash, 0, 8);

        DB::transaction(function () use ($canonical, $participations, $licenceNumber, $licenceHash, $racerHash) {
            $participations->each(function (Participant $participant) use ($canonical, $licenceNumber, $licenceHash, $racerHash) {
                $previousLicenceNumber = $participant->driver['licence_number'] ?? null;
                $previousRacerHash = $participant->racer_hash;

                // Licences are kept encrypted so the merge can be reviewed and reverted
                activity()
                    ->performedOn($participant)
                    ->event('merged')
                    ->withProperties([
                        'merged_into' => (string) $canonical->uuid,
                        'previous_racer_hash' => $previousRacerHash,
                        'racer_hash' => $racerHash,
                        'previous_licence_number' => filled($previousLicenceNumber) ? Crypt::encryptString($previousLicenceNumber) : null,
                        'licence_number' => Crypt::encryptString($licenceNumber),
                    ])
                    ->log('Driver merged');

                // The merged activity already describes the change, the automatic one would only contain the licence hash
                activity()->withoutLogging(fn () => $participant->update([
                    'driver_licence' => $licenceHash,
                    'racer_hash' => $racerHash,
                    'driver' => [
                        ...$participant->driver,
                        'licence_number' => $licenceNumber,
                    ],
                ]));

                $this->updateResults($participant, $previousRacerHash);
            });
        });
    }

    /**
     * Align the racer hash of the participant's results and link the results of the
     * same race that could not be linked because they still use the previous racer hash.
     */
    private function updateResults(Participant $participant, ?string $previousRacerHash): void
    {
        $participant->results()->update([
            'racer_hash' => $participant->racer_hash,
        ]);

        if (blank($previousRacerHash)) {
            return;
        }

        ParticipantResult::query()
            ->whereHas('runResult', fn ($query) => $query->where('race_id', $participant->race_id))
            ->whereNull('participant_id')
            ->where('racer_hash', $previousRacerHash)
            ->update([
                'participant_id' => $participant->getKey(),
                'category_id' => $participant->category_id,
                'racer_hash' => $participant->racer_hash,
            ]);
    }
}

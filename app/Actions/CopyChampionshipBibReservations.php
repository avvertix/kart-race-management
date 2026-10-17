<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\BibReservation;
use App\Models\Championship;
use App\Models\Participant;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class CopyChampionshipBibReservations
{
    public const FROM_RESERVATIONS = 'reservations';

    public const FROM_PARTICIPANTS = 'participants';

    /**
     * Copy race number reservations from a source championship to a target championship.
     *
     * Reservations can be taken from the source championship reservations (expired ones excluded)
     * or from its registered participants, one for each unique driver licence and bib pair.
     * Entries with a bib outside BibReservation::MIN_BIB and BibReservation::MAX_BIB, or conflicting with reservations or
     * participants already in the target championship (same bib, driver or licence), are skipped.
     *
     * @param  string  $from  Either FROM_RESERVATIONS or FROM_PARTICIPANTS
     * @param  CarbonInterface|null  $expiresAt  Expiration applied to all copied reservations, null for end of championship
     * @return array{copied: Collection<int, BibReservation>, skipped: Collection<int, int>} The created reservations and the sorted, unique bibs that were skipped
     */
    public function __invoke(Championship $sourceChampionship, Championship $targetChampionship, string $from, ?CarbonInterface $expiresAt = null): array
    {
        $candidates = $from === self::FROM_PARTICIPANTS
            ? $this->candidatesFromParticipants($sourceChampionship)
            : $this->candidatesFromReservations($sourceChampionship);

        $targetReservations = $targetChampionship->reservations()->get(['bib', 'driver', 'driver_licence_hash']);

        $usedBibs = $targetReservations->pluck('bib')->map(fn ($bib) => (int) $bib);
        $usedDrivers = $targetReservations->pluck('driver')->map(fn ($driver) => mb_strtolower($driver));
        $usedLicences = $targetReservations->pluck('driver_licence_hash')->filter();

        $targetParticipantBibs = Participant::query()
            ->where('championship_id', $targetChampionship->getKey())
            ->registered()
            ->get(['bib', 'driver_licence']);

        $toCreate = collect();

        $skipped = collect();

        foreach ($candidates as $candidate) {
            $licenceHash = $candidate['driver_licence_hash'];

            $bibUsedByOtherParticipant = $targetParticipantBibs->contains(fn ($participant) => (int) $participant->bib === $candidate['bib'] && $participant->driver_licence !== $licenceHash);

            $licenceUsedWithOtherBib = $licenceHash && $targetParticipantBibs->contains(fn ($participant) => $participant->driver_licence === $licenceHash && (int) $participant->bib !== $candidate['bib']);

            if ($candidate['bib'] < BibReservation::MIN_BIB
                || $candidate['bib'] > BibReservation::MAX_BIB
                || $usedBibs->contains($candidate['bib'])
                || $usedDrivers->contains(mb_strtolower($candidate['driver']))
                || ($licenceHash && $usedLicences->contains($licenceHash))
                || $bibUsedByOtherParticipant
                || $licenceUsedWithOtherBib) {
                $skipped->push($candidate['bib']);

                continue;
            }

            $usedBibs->push($candidate['bib']);
            $usedDrivers->push(mb_strtolower($candidate['driver']));

            if ($licenceHash) {
                $usedLicences->push($licenceHash);
            }

            $toCreate->push([
                ...$candidate,
                'reservation_expires_at' => $expiresAt,
            ]);
        }

        $copied = $toCreate->map(fn (array $attributes) => $targetChampionship->reservations()->create($attributes));

        return [
            'copied' => $copied,
            'skipped' => $skipped->unique()->sort()->values(),
        ];
    }

    /**
     * @return Collection<int, array{bib: int, driver: string, driver_licence: ?string, driver_licence_hash: ?string, contact_email: ?string, licence_type: mixed}>
     */
    protected function candidatesFromReservations(Championship $championship): Collection
    {
        return $championship->reservations()
            ->notExpired()
            ->orderBy('bib')
            ->get()
            ->map(fn (BibReservation $reservation) => [
                'bib' => (int) $reservation->bib,
                'driver' => $reservation->driver,
                'driver_licence' => $reservation->driver_licence,
                'driver_licence_hash' => $reservation->driver_licence_hash,
                'contact_email' => $reservation->contact_email,
                'licence_type' => $reservation->licence_type,
            ]);
    }

    /**
     * One candidate for each unique driver licence and bib pair, taking driver details from the most recent registration.
     *
     * @return Collection<int, array{bib: int, driver: string, driver_licence: ?string, driver_licence_hash: ?string, contact_email: ?string, licence_type: mixed}>
     */
    protected function candidatesFromParticipants(Championship $championship): Collection
    {
        return Participant::query()
            ->where('championship_id', $championship->getKey())
            ->registered()
            ->latest()
            ->get()
            ->unique(fn (Participant $participant) => $participant->driver_licence.'|'.$participant->bib)
            ->sortBy('bib')
            ->values()
            ->map(fn (Participant $participant) => [
                'bib' => (int) $participant->bib,
                'driver' => $participant->full_name,
                'driver_licence' => $participant->driver['licence_number'] ?? null,
                'driver_licence_hash' => $participant->driver_licence,
                'contact_email' => $participant->driver['email'] ?? null,
                'licence_type' => $participant->licence_type,
            ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\RaceNumberState;
use App\Models\BibReservation;
use App\Models\Championship;
use App\Models\Enrollment;
use App\Models\Participant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * The state of each Championship BIB (BibReservation::MIN_BIB to BibReservation::MAX_BIB) in a championship, with who
 * holds it.
 */
final class RaceNumberAvailability
{
    /**
     * @param  Collection<int, string>  $reserved  The holder of each reserved number
     * @param  Collection<int, string>  $used  The driver of each number already used
     */
    private function __construct(
        private Collection $reserved,
        private Collection $used,
    ) {}

    /**
     * Numbers with a pending or confirmed reservation, not expired, are reserved, except the ones held by the given
     * Enrollment. Numbers raced in the championship are used, except by the given licences, identified by their hash.
     *
     * @param  array<int, string>  $ownLicenceHashes
     */
    public static function in(Championship $championship, array $ownLicenceHashes = [], ?Enrollment $enrollment = null): self
    {
        /** @var Collection<int, string> $reserved */
        $reserved = BibReservation::query()
            ->inChamphionship($championship)
            ->notReleased()
            ->notExpired()
            ->whereBetween('bib', [BibReservation::MIN_BIB, BibReservation::MAX_BIB])
            ->when($enrollment, fn (Builder $query, Enrollment $enrollment) => $query->where(fn (Builder $query) => $query
                ->whereNull('enrollment_id')
                ->orWhere('enrollment_id', '!=', $enrollment->getKey())))
            ->get(['bib', 'driver'])
            ->mapWithKeys(fn (BibReservation $reservation) => [(int) $reservation->bib => $reservation->driver]);

        /** @var Collection<int, string> $used */
        $used = Participant::query()
            ->where('championship_id', $championship->getKey())
            ->whereBetween('bib', [BibReservation::MIN_BIB, BibReservation::MAX_BIB])
            ->when($ownLicenceHashes !== [], fn (Builder $query) => $query->whereNotIn('driver_licence', $ownLicenceHashes))
            ->get(['bib', 'first_name', 'last_name'])
            ->mapWithKeys(fn (Participant $participant) => [(int) $participant->bib => mb_trim($participant->first_name.' '.$participant->last_name)]);

        return new self($reserved, $used);
    }

    public function state(int $bib): RaceNumberState
    {
        return match (true) {
            $this->reserved->has($bib) => RaceNumberState::Reserved,
            $this->used->has($bib) => RaceNumberState::Used,
            default => RaceNumberState::Free,
        };
    }

    public function isFree(int $bib): bool
    {
        return $bib >= BibReservation::MIN_BIB
            && $bib <= BibReservation::MAX_BIB
            && $this->state($bib) === RaceNumberState::Free;
    }

    /**
     * Who holds the number: the driver of the reservation, or the one who raced with it. Null when free.
     */
    public function holder(int $bib): ?string
    {
        return $this->reserved->get($bib) ?? $this->used->get($bib);
    }

    /**
     * How many numbers are free between the two, both included.
     */
    public function freeCountBetween(int $from, int $to): int
    {
        return collect(range($from, $to))->filter(fn (int $bib) => $this->isFree($bib))->count();
    }
}

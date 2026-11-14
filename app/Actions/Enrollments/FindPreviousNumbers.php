<?php

declare(strict_types=1);

namespace App\Actions\Enrollments;

use App\Enums\PreviousNumberMatch;
use App\Models\BibReservation;
use App\Models\Championship;
use App\Models\Enrollment;
use App\Models\Participant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Throwable;

class FindPreviousNumbers
{
    /**
     * The Championship BIBs the driver of the Enrollment used in other championships, matched by licence number, by name
     * and birth date (when the licence number changed), or by the User who claimed or added the registration. Only the
     * number, championship, driver name and number of races are returned, most recent championship first.
     *
     * @return Collection<int, array{bib: int, championship: string, driver: string, races: int, matches: array<int, PreviousNumberMatch>}>
     */
    public function __invoke(Enrollment $enrollment): Collection
    {
        $holder = $enrollment->holder();
        $licenceHash = $enrollment->licence?->number_hash;
        $birthDate = $holder === null ? null : $this->toDateString($holder->birth_date);

        $participants = Participant::query()
            ->registered()
            ->where('championship_id', '!=', $enrollment->championship_id)
            ->whereBetween('bib', [BibReservation::MIN_BIB, BibReservation::MAX_BIB])
            ->where(fn (Builder $query) => $query
                ->where('claimed_by', $enrollment->user_id)
                ->orWhere('added_by', $enrollment->user_id)
                ->when($licenceHash, fn (Builder $query, string $hash) => $query->orWhere('driver_licence', $hash))
                ->when($holder, fn (Builder $query) => $query->orWhere(fn (Builder $query) => $query
                    ->whereRaw('LOWER(TRIM(first_name)) = ?', [$this->normalizedName($holder->first_name)])
                    ->whereRaw('LOWER(TRIM(last_name)) = ?', [$this->normalizedName($holder->last_name)]))))
            ->get(['id', 'bib', 'championship_id', 'race_id', 'first_name', 'last_name', 'driver_licence', 'driver', 'claimed_by', 'added_by']);

        $found = $participants
            ->map(fn (Participant $participant) => [
                'participant' => $participant,
                'matches' => array_values(array_filter([
                    $licenceHash !== null && $participant->driver_licence === $licenceHash ? PreviousNumberMatch::Licence : null,
                    $holder !== null && $birthDate !== null
                        && $this->normalizedName($participant->first_name) === $this->normalizedName($holder->first_name)
                        && $this->normalizedName($participant->last_name) === $this->normalizedName($holder->last_name)
                        && $this->toDateString(data_get($participant->driver, 'birth_date')) === $birthDate ? PreviousNumberMatch::NameAndBirthDate : null,
                    in_array($enrollment->user_id, [(int) $participant->claimed_by, (int) $participant->added_by], true) ? PreviousNumberMatch::User : null,
                ])),
            ])
            ->filter(fn (array $item) => $item['matches'] !== []);

        $championships = Championship::query()
            ->whereKey($found->pluck('participant.championship_id')->unique())
            ->get(['id', 'title', 'start_at'])
            ->keyBy('id');

        /** @var Collection<int, array{bib: int, championship: string, driver: string, races: int, matches: array<int, PreviousNumberMatch>}> $previousNumbers */
        $previousNumbers = $found
            ->groupBy(fn (array $item) => $item['participant']->championship_id.'-'.$item['participant']->bib)
            ->sortByDesc(fn (Collection $items) => $championships->get($items->first()['participant']->championship_id)?->start_at)
            ->map(function (Collection $items) use ($championships) {
                $participant = $items->first()['participant'];

                return [
                    'bib' => (int) $participant->bib,
                    'championship' => (string) $championships->get($participant->championship_id)?->title,
                    'driver' => mb_trim($participant->first_name.' '.$participant->last_name),
                    'races' => $items->pluck('participant.race_id')->unique()->count(),
                    'matches' => $items->pluck('matches')->flatten()->unique(fn (PreviousNumberMatch $match) => $match->value)->values()->all(),
                ];
            })
            ->values();

        return $previousNumbers;
    }

    /**
     * The name as compared: trimmed and lowercase.
     */
    private function normalizedName(string $name): string
    {
        return mb_strtolower(mb_trim($name));
    }

    /**
     * The date part of a stored birth date, null when missing or not a date.
     */
    private function toDateString(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            return CarbonImmutable::parse($value)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }
}

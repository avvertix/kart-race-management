<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Championship;
use App\Models\Participant;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class FindDuplicateDrivers
{
    /**
     * Find participants within a championship that likely belong to the same driver but
     * are identified by different racer hashes, e.g. because the licence number was
     * entered with a different format or the racer hash was not updated after a correction.
     *
     * Participants are grouped when they share the same name (in any order) or the same
     * licence number once punctuation, spaces and non-ASCII characters are ignored.
     *
     * @return Collection<int, array{participants: Collection<int, Participant>, same_name: bool, same_birth_date: bool, same_licence: bool}>
     */
    public function __invoke(Championship $championship): Collection
    {
        $participants = Participant::query()
            ->where('championship_id', $championship->getKey())
            ->registered()
            ->with('race')
            ->withCount('results')
            ->get();

        $groupOf = $this->groupByMatchingIdentity($participants);

        return $participants
            ->groupBy(fn (Participant $participant) => $groupOf[$participant->getKey()])
            ->filter(fn (Collection $group) => $group->pluck('racer_hash')->unique()->count() > 1)
            ->map(fn (Collection $group) => [
                'participants' => $group->sortBy(fn (Participant $participant) => $participant->race->event_start_at)->values(),
                'same_name' => $group->map(fn (Participant $participant) => $this->nameKey($participant))->unique()->count() === 1,
                'same_birth_date' => $group->map(fn (Participant $participant) => $this->birthDate($participant))->unique()->count() === 1,
                'same_licence' => $group->map(fn (Participant $participant) => $this->licenceKey($participant))->unique()->count() === 1,
            ])
            ->sortBy(fn (array $group) => Str::lower($group['participants']->first()->last_name))
            ->values();
    }

    /**
     * Link participants sharing a name or a licence key into the same group.
     *
     * @param  Collection<int, Participant>  $participants
     * @return array<int, int> participant id => group id
     */
    private function groupByMatchingIdentity(Collection $participants): array
    {
        $parent = $participants->mapWithKeys(fn (Participant $participant) => [$participant->getKey() => $participant->getKey()])->all();

        $find = function (int $id) use (&$parent): int {
            while ($parent[$id] !== $id) {
                $parent[$id] = $parent[$parent[$id]];
                $id = $parent[$id];
            }

            return $id;
        };

        $keys = [
            fn (Participant $participant) => $this->nameKey($participant),
            fn (Participant $participant) => $this->licenceKey($participant),
        ];

        foreach ($keys as $key) {
            $participants
                ->groupBy($key)
                ->reject(fn (Collection $group, $value) => blank($value))
                ->each(function (Collection $group) use (&$parent, $find) {
                    $root = $find($group->first()->getKey());

                    foreach ($group as $participant) {
                        $parent[$find($participant->getKey())] = $root;
                    }
                });
        }

        return collect($parent)->mapWithKeys(fn ($value, $id) => [$id => $find($id)])->all();
    }

    private function nameKey(Participant $participant): string
    {
        return Str::of("{$participant->first_name} {$participant->last_name}")
            ->ascii()
            ->lower()
            ->explode(' ')
            ->filter()
            ->sort()
            ->implode(' ');
    }

    private function licenceKey(Participant $participant): string
    {
        return (string) preg_replace('/[^A-Z0-9]/', '', Str::upper(Str::ascii($participant->driver['licence_number'] ?? '')));
    }

    private function birthDate(Participant $participant): ?string
    {
        $birthDate = $participant->driver['birth_date'] ?? null;

        return filled($birthDate) ? mb_substr((string) $birthDate, 0, 10) : null;
    }
}

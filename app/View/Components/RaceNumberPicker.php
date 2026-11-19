<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Actions\Enrollments\PickRaceNumber;
use App\Enums\RaceNumberState;
use App\Models\BibReservation;
use App\Models\Championship;
use App\Models\Enrollment;
use App\Models\Licence;
use App\Models\Participant;
use App\Models\User;
use App\Support\RaceNumberAvailability;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

/**
 * Pick a Championship BIB: the numbers the User used before, an overview of the hundreds with their free numbers, and
 * the numbers of one hundred. The state of every number is computed once and the picking
 * happens in the browser; the pick is sent with the surrounding form as `bib` and reserved only when the form is
 * submitted, where App\Actions\Enrollments\PickRaceNumber checks it again.
 */
class RaceNumberPicker extends Component
{
    /**
     * The state of each number, one character per number from index 1: `f` free, `r` reserved, `u` already used.
     */
    public string $states;

    /**
     * Who holds each taken number, by number: only for organizers.
     *
     * @var array<int, string>
     */
    public array $holders = [];

    /**
     * The free numbers of each hundred, 0 for 1–99 and 9 for 900–999.
     *
     * @var array<int, array{hundred: int, from: int, to: int, free: int}>
     */
    public array $hundreds;

    public ?int $selected = null;

    public int $hundred = 0;

    /**
     * The numbers the User raced with before, one per championship and number, most recent first.
     *
     * @var Collection<int, array{bib: int, championship: ?string, driver: string, state: RaceNumberState, note: ?string}>
     */
    public Collection $previousNumbers;

    /**
     * What the licences in play already hold, as sentences.
     *
     * @var Collection<int, string>
     */
    public Collection $heldNumbers;

    /**
     * @param  ?Enrollment  $enrollment  The Enrollment changing its pick: its own number is shown as free.
     * @param  bool  $showHolders  Whether to show who holds each number: only to organizers.
     * @param  bool  $showHeld  Whether to tell the numbers the User's Licences already hold: off where the surrounding form
     *                          shows it for the chosen Licence.
     */
    public function __construct(
        public Championship $championship,
        public ?Enrollment $enrollment = null,
        bool $showHolders = false,
        bool $showHeld = true,
    ) {
        $user = auth()->user();
        $showHolders = $showHolders && ($user?->can('review', Enrollment::class) ?? false);

        $this->enrollment?->loadMissing('licence');

        $availability = RaceNumberAvailability::in($championship, $this->ownLicenceHashes($user, $showHolders), $this->enrollment);

        $this->states = '-'.collect(range(BibReservation::MIN_BIB, BibReservation::MAX_BIB))
            ->map(fn (int $bib) => match ($availability->state($bib)) {
                RaceNumberState::Free => 'f',
                RaceNumberState::Reserved => 'r',
                RaceNumberState::Used => 'u',
            })
            ->join('');

        if ($showHolders) {
            $this->holders = collect(range(BibReservation::MIN_BIB, BibReservation::MAX_BIB))
                ->mapWithKeys(fn (int $bib) => [$bib => $availability->holder($bib)])
                ->filter()
                ->all();
        }

        $this->hundreds = collect(range(0, 9))
            ->map(fn (int $hundred) => [
                'hundred' => $hundred,
                'from' => max(BibReservation::MIN_BIB, $hundred * 100),
                'to' => $hundred * 100 + 99,
                'free' => $availability->freeCountBetween(max(BibReservation::MIN_BIB, $hundred * 100), $hundred * 100 + 99),
            ])
            ->all();

        $current = old('bib', $this->enrollment?->reservations()->notReleased()->value('bib'));

        if (filled($current)) {
            $this->selected = (int) $current;
            $this->hundred = max(0, min(9, intdiv($this->selected, 100)));
        }

        $this->previousNumbers = $this->previousNumbers($user, $showHolders, $availability);
        $this->heldNumbers = $showHeld ? $this->heldNumbers($user, $showHolders) : collect();
    }

    public function render(): View|Closure|string
    {
        return view('components.race-number-picker', [
            'labels' => [
                'states' => collect(RaceNumberState::cases())->mapWithKeys(fn (RaceNumberState $state) => [mb_substr($state->value, 0, 1) => $state->label()])->all(),
                'pick' => __('your pick'),
                'pickShort' => __('your pick: :bib'),
                'outOfRange' => __('Pick a number between :min and :max.', ['min' => BibReservation::MIN_BIB, 'max' => BibReservation::MAX_BIB]),
                'taken' => __('Race number :bib is :state.'),
                'picked' => __('Your pick: :bib. It is held for you until the organizer confirms the enrollment; numbers are first confirmed, first served.'),
                'nothing' => __('Pick a free number.'),
                'range' => __('Race numbers :from to :to'),
            ],
        ]);
    }

    /**
     * The hashes of the licences whose numbers are not already used, as PickRaceNumber judges them: the Enrollment's
     * Licence when changing a pick, otherwise the licences the User manages, one of which they are about to enroll.
     *
     * @return array<int, string>
     */
    private function ownLicenceHashes(?User $user, bool $showHolders): array
    {
        if ($this->enrollment !== null) {
            return array_filter([$this->enrollment->licence?->number_hash]);
        }

        if ($user === null || $showHolders) {
            return [];
        }

        return Licence::query()->managedBy($user)->pluck('number_hash')->all();
    }

    /**
     * The numbers already reserved for the licences in play, e.g. copied from last season: the Enrollment's Licence when
     * changing a pick, otherwise the driver licences the User manages that can enroll.
     *
     * @return Collection<int, string>
     */
    private function heldNumbers(?User $user, bool $showHolders): Collection
    {
        $licences = match (true) {
            $this->enrollment !== null => collect([$this->enrollment->licence])->filter(),
            $user !== null && ! $showHolders => Licence::query()->managedBy($user)->enrollableIn($this->championship)->get(),
            default => collect(),
        };

        return $licences
            ->map(fn (Licence $licence) => [
                'licence' => $licence,
                'reservation' => PickRaceNumber::reservationHeldBy($this->championship, $licence, $this->enrollment),
            ])
            ->filter(fn (array $held) => $held['reservation'] !== null)
            ->map(fn (array $held) => (string) __('Number :bib is reserved for :driver.', ['bib' => $held['reservation']->bib, 'driver' => $held['licence']->holder->fullName()]))
            ->values()
            ->toBase();
    }

    /**
     * The numbers of the Participants the User claimed or added, one per championship and number, most recent first.
     *
     * @return Collection<int, array{bib: int, championship: ?string, driver: string, state: RaceNumberState, note: ?string}>
     */
    private function previousNumbers(?User $user, bool $showHolders, RaceNumberAvailability $availability): Collection
    {
        if ($user === null || $showHolders) {
            return collect();
        }

        $participants = Participant::query()
            ->where(fn (Builder $query) => $query->where('claimed_by', $user->getKey())->orWhere('added_by', $user->getKey()))
            ->whereBetween('bib', [BibReservation::MIN_BIB, BibReservation::MAX_BIB])
            ->latest()
            ->limit(50)
            ->get(['bib', 'championship_id', 'first_name', 'last_name'])
            ->unique(fn (Participant $participant) => $participant->championship_id.'-'.$participant->bib)
            ->take(12);

        /** @var Collection<int, string> $titles */
        $titles = Championship::query()->whereKey($participants->pluck('championship_id'))->pluck('title', 'id');

        /** @var Collection<int, array{bib: int, championship: ?string, driver: string, state: RaceNumberState, note: ?string}> $previous */
        $previous = $participants
            ->map(function (Participant $participant) use ($titles, $availability) {
                $bib = (int) $participant->bib;
                $driver = mb_trim($participant->first_name.' '.$participant->last_name);
                $state = $availability->state($bib);

                return [
                    'bib' => $bib,
                    'championship' => $titles->get($participant->championship_id),
                    'driver' => $driver,
                    'state' => $state,
                    'note' => match (true) {
                        $state === RaceNumberState::Free => null,
                        $state === RaceNumberState::Reserved && mb_strtolower((string) $availability->holder($bib)) === mb_strtolower($driver) => __('Reserved for :driver.', ['driver' => $driver]),
                        default => __('Taken by another driver.'),
                    },
                ];
            })
            ->values()
            ->toBase();

        return $previous;
    }
}

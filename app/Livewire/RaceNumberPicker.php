<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\Enrollments\PickRaceNumber;
use App\Actions\GenerateRaceNumber;
use App\Models\BibReservation;
use App\Models\Championship;
use App\Models\Enrollment;
use App\Models\Licence;
use App\Models\Participant;
use App\Models\User;
use App\Support\RaceNumberAvailability;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Pick a Championship BIB: an overview of the hundreds with their free numbers, the numbers of one hundred, a field to
 * jump to a number, and the numbers the User used before. The pick is sent with the surrounding form as `bib`; the
 * number is reserved only when the form is submitted, see App\Actions\Enrollments\PickRaceNumber.
 */
class RaceNumberPicker extends Component
{
    #[Locked]
    public Championship $championship;

    /**
     * The Enrollment changing its pick: its own number is shown as free.
     */
    #[Locked]
    public ?int $enrollmentId = null;

    /**
     * Whether to show who holds each number: only to organizers.
     */
    #[Locked]
    public bool $showHolders = false;

    /**
     * Whether to tell the numbers the User's Licences already hold: off where the surrounding form shows it for the chosen
     * Licence.
     */
    #[Locked]
    public bool $showHeld = true;

    public ?int $selected = null;

    public int $hundred = 0;

    public string $jumpTo = '';

    #[Locked]
    public ?string $notice = null;

    public function mount(Championship $championship, ?Enrollment $enrollment = null, bool $showHolders = false, bool $showHeld = true): void
    {
        $this->showHeld = $showHeld;
        $this->championship = $championship;
        $this->enrollmentId = $enrollment?->getKey();
        $this->showHolders = $showHolders && (auth()->user()?->can('review', Enrollment::class) ?? false);

        $current = old('bib', $enrollment?->reservations()->notReleased()->value('bib'));

        if (filled($current)) {
            $this->selected = (int) $current;
            $this->hundred = intdiv($this->selected, 100);
        }
    }

    /**
     * Show the numbers of one hundred: 0 for 1–99, 9 for 900–999.
     */
    public function zoom(int $hundred): void
    {
        $this->hundred = max(0, min(9, $hundred));
    }

    /**
     * Pick the number when free, otherwise tell why it can't be picked.
     */
    public function pick(int $bib): void
    {
        $availability = $this->availability();

        if (! $availability->isFree($bib)) {
            $this->notice = $bib < BibReservation::MIN_BIB || $bib > BibReservation::MAX_BIB
                ? __('Pick a number between :min and :max.', ['min' => BibReservation::MIN_BIB, 'max' => BibReservation::MAX_BIB])
                : __('Race number :bib is :state.', ['bib' => $bib, 'state' => $availability->state($bib)->label()]);

            return;
        }

        $this->selected = $bib;
        $this->notice = null;
    }

    /**
     * Go to the typed number and pick it when free.
     */
    public function jump(): void
    {
        $this->validate([
            'jumpTo' => ['required', 'integer', 'min:'.BibReservation::MIN_BIB, 'max:'.BibReservation::MAX_BIB],
        ]);

        $bib = (int) $this->jumpTo;

        $this->zoom(intdiv($bib, 100));
        $this->pick($bib);
    }

    public function render(GenerateRaceNumber $generateRaceNumber): View
    {
        $this->zoom($this->hundred);

        $availability = $this->availability();

        $previousNumbers = $this->previousNumbers();

        $from = max(BibReservation::MIN_BIB, $this->hundred * 100);
        $to = $this->hundred * 100 + 99;

        return view('livewire.race-number-picker', [
            'availability' => $availability,
            'hundreds' => collect(range(0, 9))->map(fn (int $hundred) => [
                'hundred' => $hundred,
                'from' => max(BibReservation::MIN_BIB, $hundred * 100),
                'to' => $hundred * 100 + 99,
                'free' => $availability->freeCountBetween(max(BibReservation::MIN_BIB, $hundred * 100), $hundred * 100 + 99),
            ]),
            'numbers' => range($from, $to),
            'suggestions' => collect($generateRaceNumber($this->championship, 6))->filter(fn (int $bib) => $availability->isFree($bib))->values(),
            'previousNumbers' => $previousNumbers,
            'heldNumbers' => $this->showHeld ? $this->heldNumbers() : collect(),
            'championshipTitles' => Championship::query()->whereKey($previousNumbers->pluck('championship_id'))->pluck('title', 'id'),
        ]);
    }

    /**
     * The Enrollment changing its pick, loaded once per request.
     */
    #[Computed]
    public function enrollment(): ?Enrollment
    {
        return $this->enrollmentId === null ? null : Enrollment::query()->with('licence')->find($this->enrollmentId);
    }

    private function availability(): RaceNumberAvailability
    {
        return RaceNumberAvailability::in(
            $this->championship,
            $this->ownLicenceHashes(),
            $this->enrollment(),
        );
    }

    /**
     * The hashes of the licences whose numbers are not already used, as PickRaceNumber judges them: the Enrollment's
     * Licence when changing a pick, otherwise the licences the User manages, one of which they are about to enroll.
     *
     * @return array<int, string>
     */
    private function ownLicenceHashes(): array
    {
        if ($this->enrollmentId !== null) {
            return array_filter([$this->enrollment()?->licence?->number_hash]);
        }

        $user = auth()->user();

        if (! $user instanceof User || $this->showHolders) {
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
    private function heldNumbers(): Collection
    {
        $enrollment = $this->enrollment();
        $user = auth()->user();

        $licences = match (true) {
            $enrollment !== null => collect([$enrollment->licence])->filter(),
            $user instanceof User && ! $this->showHolders => Licence::query()->managedBy($user)->enrollableIn($this->championship)->get(),
            default => collect(),
        };

        return $licences
            ->map(fn (Licence $licence) => [
                'licence' => $licence,
                'reservation' => PickRaceNumber::reservationHeldBy($this->championship, $licence, $enrollment),
            ])
            ->filter(fn (array $held) => $held['reservation'] !== null)
            ->map(fn (array $held) => (string) __('Number :bib is reserved for :driver.', ['bib' => $held['reservation']->bib, 'driver' => $held['licence']->holder->fullName()]))
            ->values();
    }

    /**
     * The numbers of the Participants the User claimed or added, one per championship and number, most recent first.
     *
     * @return Collection<int, Participant>
     */
    private function previousNumbers(): Collection
    {
        $user = auth()->user();

        if (! $user instanceof User || $this->showHolders) {
            return collect();
        }

        return Participant::query()
            ->where(fn (Builder $query) => $query->where('claimed_by', $user->getKey())->orWhere('added_by', $user->getKey()))
            ->whereBetween('bib', [BibReservation::MIN_BIB, BibReservation::MAX_BIB])
            ->latest()
            ->limit(50)
            ->get()
            ->unique(fn (Participant $participant) => $participant->championship_id.'-'.$participant->bib)
            ->take(12)
            ->values();
    }
}

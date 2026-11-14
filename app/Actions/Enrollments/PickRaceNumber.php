<?php

declare(strict_types=1);

namespace App\Actions\Enrollments;

use App\Enums\BibReservationStatus;
use App\Enums\EnrollmentStatus;
use App\Models\BibReservation;
use App\Models\Championship;
use App\Models\Enrollment;
use App\Models\Licence;
use App\Models\User;
use App\Support\RaceNumberAvailability;
use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PickRaceNumber
{
    /**
     * Pick, or change, the race number of a submitted Enrollment: a Pending reservation held until the organizer
     * confirms the Enrollment. First come, first served: the number is locked while it is checked and reserved. Once
     * the organizer confirmed the number, only the organizer changes it, and it stays confirmed.
     *
     * @throws ValidationException when the number is not valid nor free, or the Enrollment can't pick one
     */
    public function __invoke(Enrollment $enrollment, mixed $bib, User $user, bool $byOrganizer = false): BibReservation
    {
        $bib = $this->validated($bib);

        return $this->whileHolding($bib, fn () => DB::transaction(fn () => $this->assign($enrollment, $bib, $user, $byOrganizer)));
    }

    /**
     * The reservation the Licence already holds in the championship apart from the Enrollment, e.g. one copied from last
     * season: the Licence keeps that number and can't pick another.
     */
    public static function reservationHeldBy(Championship $championship, ?Licence $licence, ?Enrollment $enrollment = null): ?BibReservation
    {
        if ($licence === null) {
            return null;
        }

        return BibReservation::query()
            ->inChamphionship($championship)
            ->notReleased()
            ->notExpired()
            ->licenceHash($licence->number_hash)
            ->when($enrollment, fn (Builder $query, Enrollment $enrollment) => $query->where(fn (Builder $query) => $query
                ->whereNull('enrollment_id')
                ->orWhere('enrollment_id', '!=', $enrollment->getKey())))
            ->first();
    }

    /**
     * The number as an integer, when it is a Championship BIB.
     *
     * @throws ValidationException
     */
    public function validated(mixed $bib): int
    {
        Validator::make(['bib' => $bib], [
            'bib' => ['required', 'integer', 'min:'.BibReservation::MIN_BIB, 'max:'.BibReservation::MAX_BIB],
        ])->validate();

        return (int) $bib;
    }

    /**
     * Run the callback holding the lock on the number, the same lock race registration takes on it, so that an
     * enrollment pick and a race registration of the same number wait for each other.
     *
     * @template TResult
     *
     * @param  Closure(): TResult  $callback
     * @return TResult
     *
     * @throws ValidationException when the number stays locked by someone else
     */
    public function whileHolding(int $bib, Closure $callback): mixed
    {
        try {
            return Cache::lock("participant:{$bib}", 10)->block(5, $callback);
        } catch (LockTimeoutException) {
            throw ValidationException::withMessages(['bib' => __('Race number :bib is being picked by someone else, try again.', ['bib' => $bib])]);
        }
    }

    /**
     * Reserve the number for the Enrollment, or move its pending reservation to it. Call it inside whileHolding() and a
     * transaction.
     *
     * @throws ValidationException
     */
    public function assign(Enrollment $enrollment, int $bib, User $user, bool $byOrganizer = false): BibReservation
    {
        $locked = $enrollment->lockInStatus([EnrollmentStatus::Submitted], 'bib', __('The race number can be changed only until the enrollment is confirmed.'));

        if (! $byOrganizer && $locked->hasConfirmedRaceNumber()) {
            throw ValidationException::withMessages(['bib' => __('The organizer confirmed the race number: ask them to change it.')]);
        }

        $championship = $locked->championship;

        if (! $locked->grantsBibReservation()) {
            throw ValidationException::withMessages(['bib' => __('Enrolling in this championship does not reserve a race number.')]);
        }

        $licence = $locked->licence;

        $heldByLicence = self::reservationHeldBy($championship, $licence, $locked);

        if ($heldByLicence !== null) {
            throw ValidationException::withMessages(['bib' => __('The licence already has race number :bib reserved.', ['bib' => $heldByLicence->bib])]);
        }

        $availability = RaceNumberAvailability::in($championship, array_filter([$licence?->number_hash]), $locked);

        if (! $availability->isFree($bib)) {
            throw ValidationException::withMessages(['bib' => __('Race number :bib is not available anymore, pick another one.', ['bib' => $bib])]);
        }

        $reservation = $locked->reservations()->notReleased()->first();

        if ($reservation !== null) {
            $reservation->update(['bib' => $bib]);

            return $reservation;
        }

        return $championship->reservations()->create([
            'enrollment_id' => $locked->getKey(),
            'bib' => $bib,
            'status' => BibReservationStatus::Pending,
            'driver' => $locked->holder()?->fullName() ?? '',
            'driver_licence' => $licence?->number,
            'driver_licence_hash' => $licence?->number_hash,
            'licence_type' => $licence?->race_licence_category,
            'licence_id' => $licence?->getKey(),
            'created_by_user_id' => $user->getKey(),
        ]);
    }
}

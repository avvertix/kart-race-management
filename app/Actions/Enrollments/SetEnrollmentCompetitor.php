<?php

declare(strict_types=1);

namespace App\Actions\Enrollments;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Models\Licence;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SetEnrollmentCompetitor
{
    /**
     * Name, change or remove the Competitor of a submitted Enrollment: an adult at the championship start, other than the
     * driver, including the competitor licence, of a type the instance handles. The driver picks among the Licences they manage (`$among`), the
     * organizer among all, e.g. for an Enrollment the organizer created without one.
     *
     * @throws ValidationException when the Enrollment is not submitted or the Licence can't be the Competitor
     */
    public function __invoke(Enrollment $enrollment, ?string $competitorUlid, ?User $among): Enrollment
    {
        return DB::transaction(function () use ($enrollment, $competitorUlid, $among) {
            $locked = $enrollment->lockInStatus([EnrollmentStatus::Submitted], 'competitor_licence', __('Only a submitted enrollment can change competitor.'));

            $competitor = null;

            if (filled($competitorUlid)) {
                $competitor = Licence::query()
                    ->when($among, fn ($query, User $user) => $query->managedBy($user))
                    ->ofEnabledType()
                    ->where('ulid', $competitorUlid)
                    ->first();

                if ($competitor !== null && ! Enrollment::isValidCompetitor($locked->championship, $competitor, $locked->holder())) {
                    $competitor = null;
                }

                if ($competitor === null) {
                    throw ValidationException::withMessages(['competitor_licence' => __('The competitor must be an adult other than the driver.')]);
                }
            }

            $locked->update(['competitor_licence_id' => $competitor?->getKey()]);

            return $locked;
        });
    }
}

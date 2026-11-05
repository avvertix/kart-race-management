<?php

declare(strict_types=1);

namespace App\Support;

use App\Actions\Enrollments\PickRaceNumber;
use App\Actions\Enrollments\SubmitEnrollment;
use App\Models\Championship;
use App\Models\Licence;
use App\Models\LicenceRequest;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * The choices of what to enroll in a championship, as `subject` values of the enrollment forms.
 */
class EnrollmentSubjects
{
    /**
     * What the User can enroll: their driver Licences and open Licence Requests for the championship that are not enrolled
     * yet, then the renewal of their earlier Licences of the Drivers not among those, and of a licence not on the
     * platform. Each with whether the Driver is underage and the race number the Licence already holds.
     *
     * @return Collection<int, array{value: string, label: string, kind: string, underage: bool, heldBib: ?int}>
     */
    public static function for(User $user, Championship $championship): Collection
    {
        $active = $championship->enrollments()->active()->get(['licence_id', 'licence_request_id']);

        $licences = Licence::query()
            ->managedBy($user)
            ->enrollableIn($championship)
            ->whereNotIn('id', $active->pluck('licence_id')->filter()->all())
            ->orderBy('id')
            ->get()
            ->map(fn (Licence $licence) => [
                'value' => 'licence:'.$licence->ulid,
                'label' => $licence->holder->fullName().' · '.$licence->licenceTypeLabel().' · '.$licence->number,
                'kind' => 'licence',
                'underage' => $championship->requiresCompetitorFor($licence->holder),
                'heldBib' => PickRaceNumber::reservationHeldBy($championship, $licence)?->bib,
            ]);

        $licenceRequests = LicenceRequest::query()
            ->requestedBy($user)
            ->enrollableIn($championship)
            ->whereNotIn('id', $active->pluck('licence_request_id')->filter()->all())
            ->latest()
            ->get()
            ->map(fn (LicenceRequest $licenceRequest) => [
                'value' => 'licence_request:'.$licenceRequest->ulid,
                'label' => collect([$licenceRequest->holder?->fullName(), $licenceRequest->licenceTypeLabel(), (string) __('Licence request: the enrollment waits for the licence')])->filter()->join(' · '),
                'kind' => 'licence',
                'underage' => $licenceRequest->holder !== null && $championship->requiresCompetitorFor($licenceRequest->holder),
                'heldBib' => null,
            ]);

        $subjects = $licences->toBase()->concat($licenceRequests->toBase());

        $year = $championship->start_at->year;

        $coveredHolders = Licence::query()->managedBy($user)->enrollableIn($championship)->get()->map(fn (Licence $licence) => $licence->holder->identityKey())
            ->concat(LicenceRequest::query()->requestedBy($user)->enrollableIn($championship)->get()->map(fn (LicenceRequest $licenceRequest) => $licenceRequest->holder?->identityKey()))
            ->filter()
            ->all();

        $renewals = Licence::query()
            ->managedBy($user)
            ->renewableFor($championship)
            ->orderByDesc('validity_year')
            ->get()
            ->unique(fn (Licence $licence) => $licence->holder->identityKey())
            ->reject(fn (Licence $licence) => in_array($licence->holder->identityKey(), $coveredHolders, true))
            ->map(fn (Licence $licence) => [
                'value' => 'renew:'.$licence->ulid,
                'label' => __('Renew for :year', ['year' => $year]).' · '.$licence->holder->fullName().' · '.$licence->licenceTypeLabel().' · '.$licence->number,
                'kind' => 'renewal',
                'underage' => $championship->requiresCompetitorFor($licence->holder),
                'heldBib' => null,
            ]);

        return $subjects
            ->concat($renewals->toBase())
            ->push([
                'value' => 'renew:'.SubmitEnrollment::RENEW_NEW,
                'label' => __('Renew for :year', ['year' => $year]).' · '.__('a licence that is not in your account'),
                'kind' => 'renewal',
                'underage' => false,
                'heldBib' => null,
            ])
            ->values();
    }
}

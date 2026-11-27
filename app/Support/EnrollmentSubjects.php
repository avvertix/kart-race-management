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
     * platform. Each with whether the Driver is underage and the race number the Licence already holds, and how the forms
     * show it: its group (`ready`, `waiting`, `renewal` or `other`), the Driver's name and a detail line.
     *
     * @return Collection<int, array{value: string, label: string, kind: string, group: string, name: string, detail: string, category: ?int, underage: bool, parentCompetitor: bool, heldBib: ?int}>
     */
    public static function for(User $user, Championship $championship): Collection
    {
        $active = $championship->enrollments()->active()->get(['licence_id', 'licence_request_id']);

        $licences = Licence::query()
            ->managedBy($user)
            ->enrollableIn($championship)
            ->whereNotIn('id', $active->pluck('licence_id')->filter()->all())
            ->with('licenceRequest')
            ->orderBy('id')
            ->get()
            ->map(fn (Licence $licence) => [
                'value' => 'licence:'.$licence->ulid,
                'label' => $licence->holder->fullName().' · '.$licence->licenceTypeLabel().' · '.$licence->number,
                'kind' => 'licence',
                'group' => 'ready',
                'name' => $licence->holder->fullName(),
                'detail' => $licence->licenceTypeLabel().' · '.$licence->number,
                'category' => $licence->race_licence_category?->value,
                'underage' => $championship->requiresCompetitorFor($licence->holder),
                'parentCompetitor' => SubmitEnrollment::hasParentAsCompetitor($licence),
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
                'group' => 'waiting',
                'name' => $licenceRequest->holder?->fullName() ?? (string) __('Holder not filled in yet'),
                'detail' => collect([$licenceRequest->licenceTypeLabel(), $licenceRequest->status->requesterLabel()])->filter()->join(' · '),
                'category' => $licenceRequest->licenceTypeData()?->raceLicenceCategory?->value,
                'underage' => $licenceRequest->holder !== null && $championship->requiresCompetitorFor($licenceRequest->holder),
                'parentCompetitor' => true,
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
                'group' => 'renewal',
                'name' => $licence->holder->fullName(),
                'detail' => __(':type licence of :year', ['type' => $licence->licenceTypeLabel(), 'year' => $licence->validity_year]).' · '.$licence->number,
                'category' => $licence->race_licence_category?->value,
                'underage' => $championship->requiresCompetitorFor($licence->holder),
                'parentCompetitor' => true,
                'heldBib' => null,
            ]);

        return $subjects
            ->concat($renewals->toBase())
            ->push([
                'value' => 'renew:'.SubmitEnrollment::RENEW_NEW,
                'label' => __('Renew for :year', ['year' => $year]).' · '.__('a licence that is not in your account'),
                'kind' => 'renewal',
                'group' => 'other',
                'name' => (string) __('The driver is not listed'),
                'detail' => (string) __('Renew a licence that is not in your account: you type the driver\'s details and last year\'s number in the request.'),
                'category' => null,
                'underage' => false,
                'parentCompetitor' => true,
                'heldBib' => null,
            ])
            ->values();
    }
}

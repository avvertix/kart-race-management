<?php

declare(strict_types=1);

namespace App\Actions;

use App\Actions\Enrollments\PickRaceNumber;
use App\Data\HolderData;
use App\Enums\LicenceRole;
use App\Models\CompetitorLicence;
use App\Models\Licence;
use App\Models\Participant;
use App\Models\Race;
use App\Models\User;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class PrepareRegistrationLicencePickers
{
    /**
     * The competitor choice that declines the one of the previous registration.
     */
    public const NO_COMPETITOR = 'none';

    /**
     * The Licences the User manages that can fill a race registration, valid in the race's year and of a type the
     * instance handles: drivers, competitors and mechanics. With the picked ones, the template that pre-fills the form;
     * the fields stay editable. Without a picked competitor, the one of the User's previous registration of the driver is
     * used, unless NO_COMPETITOR is picked. Licences on the platform are of the national provider: the competitor licence
     * type is local. The race number reserved for the driver's Licence is pre-filled.
     *
     * @param  array{driver_licence?: ?string, competitor_licence?: ?string, mechanic_licence?: ?string}  $picked  ULIDs
     * @return array{
     *     drivers: Collection<int, Licence>,
     *     competitors: Collection<int, Licence>,
     *     mechanics: Collection<int, Licence>,
     *     driver: ?Licence,
     *     competitor: ?Licence,
     *     mechanic: ?Licence,
     *     template: ?object,
     * }
     */
    public function __invoke(User $user, Race $race, array $picked): array
    {
        /** @var CarbonInterface $startsAt */
        $startsAt = $race->event_start_at;
        $year = $startsAt->year;

        $licences = fn (Closure $scope) => Licence::query()
            ->managedBy($user)
            ->where('validity_year', $year)
            ->ofEnabledType()
            ->where($scope)
            ->orderBy('id')
            ->get();

        $drivers = $licences(fn (Builder $query) => $query->where('role', LicenceRole::Driver));
        $competitors = $licences(fn (Builder $query) => $query->where('includes_competitor', true));
        $mechanics = $licences(fn (Builder $query) => $query->where('role', LicenceRole::Mechanic));

        $driver = $drivers->firstWhere('ulid', $picked['driver_licence'] ?? null);
        $competitor = $competitors->firstWhere('ulid', $picked['competitor_licence'] ?? null);
        $mechanic = $mechanics->firstWhere('ulid', $picked['mechanic_licence'] ?? null);

        $previousCompetitor = null;

        if ($driver !== null && $competitor === null && ($picked['competitor_licence'] ?? null) !== self::NO_COMPETITOR) {
            $previous = Participant::query()
                ->where(fn (Builder $query) => $query->where('added_by', $user->getKey())->orWhere('claimed_by', $user->getKey()))
                ->where('driver_licence', $driver->number_hash)
                ->whereNotNull('competitor_licence')
                ->latest()
                ->first();

            $competitor = $previous === null ? null : $competitors->firstWhere('number_hash', $previous->competitor_licence);
            $previousCompetitor = $competitor === null ? $previous?->competitor : null;
        }

        $template = $driver === null && $competitor === null && $mechanic === null ? null : (object) [
            'bib' => $driver === null ? null : PickRaceNumber::reservationHeldBy($race->championship()->sole(), $driver)?->bib,
            'driver' => $driver === null ? null : [
                ...$this->personFields($driver->holder),
                'licence_type' => $driver->race_licence_category?->value,
                'licence_number' => $driver->number,
            ],
            'competitor' => $competitor === null ? $previousCompetitor : [
                ...$this->personFields($competitor->holder),
                'licence_type' => CompetitorLicence::LOCAL->value,
                'licence_number' => $competitor->number,
            ],
            'mechanic' => $mechanic === null ? null : [
                'name' => $mechanic->holder->fullName(),
                'licence_number' => $mechanic->number,
            ],
        ];

        return [
            'drivers' => $drivers,
            'competitors' => $competitors,
            'mechanics' => $mechanics,
            'driver' => $driver,
            'competitor' => $competitor,
            'mechanic' => $mechanic,
            'template' => $template,
        ];
    }

    /**
     * The registration fields of a person, as the form reads them from a Participant.
     *
     * @return array<string, mixed>
     */
    private function personFields(HolderData $holder): array
    {
        return [
            'first_name' => $holder->first_name,
            'last_name' => $holder->last_name,
            'fiscal_code' => $holder->fiscal_code,
            'nationality' => $holder->nationality,
            'email' => $holder->email,
            'phone' => $holder->phone,
            'birth_date' => $holder->birth_date,
            'birth_place' => $holder->birth_place,
            'sex' => $holder->sex->value,
            'residence_address' => [
                'address' => $holder->residence_address->address,
                'city' => $holder->residence_address->city,
                'province' => $holder->residence_address->province,
                'postal_code' => $holder->residence_address->postal_code,
            ],
        ];
    }
}

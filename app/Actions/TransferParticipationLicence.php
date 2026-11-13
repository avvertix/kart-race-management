<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\HolderData;
use App\Data\LicenceTypeData;
use App\Enums\LicenceProvider;
use App\Enums\LicenceRole;
use App\Enums\LicenceSource;
use App\Models\DriverLicence;
use App\Models\Entitlement;
use App\Models\Licence;
use App\Models\Participant;
use App\Models\Sex;
use App\Models\User;
use App\Support\LicenceCatalogue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;

/**
 * Turn a race participation linked to the User into a Licence they manage, so it can be renewed or enrolled. Only the
 * latest participation of each licence number counts, once the organizer confirmed it: its details were reviewed at
 * the race, so the Licence starts verified, for the year of the participation's championship. Foreign, daily and
 * EasyRace licences are not issued by a provider the platform manages, so they are never transferred.
 */
class TransferParticipationLicence
{
    /**
     * The licence categories a provider the platform manages issues.
     */
    private const TRANSFERABLE_CATEGORIES = [DriverLicence::LOCAL_NATIONAL, DriverLicence::LOCAL_INTERNATIONAL];

    /**
     * Create the verified Licence from the participation, with the Licence Type and holder given, e.g. the defaults or
     * what the User completed. The number and year are the participation's.
     *
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException when the input is incomplete, or the Licence of that year already exists
     */
    public function __invoke(User $user, Participant $participation, array $input): Licence
    {
        Validator::make($input, [
            'provider' => ['required', Rule::enum(LicenceProvider::class)->only(LicenceCatalogue::providers())],
        ])->validate();

        $provider = LicenceProvider::from($input['provider']);

        $validated = Validator::make($input, [
            'licence_type' => ['required', 'string', Rule::in(LicenceCatalogue::types($provider)->where('role', LicenceRole::Driver)->pluck('code')->all())],
            'driver_only' => ['nullable', 'boolean'],
            'holder' => ['required', 'array'],
            ...HolderData::validationRules($input),
            'holder.sex' => ['required', Rule::in([Sex::MALE->value, Sex::FEMALE->value])],
        ])->validate();

        /** @var LicenceTypeData $licenceType */
        $licenceType = LicenceCatalogue::find($provider, $validated['licence_type']);

        $validityYear = self::validityYear($participation);

        try {
            $licence = Licence::create([
                ...$licenceType->licenceAttributes(includesCompetitor: ! ($validated['driver_only'] ?? false)),
                'user_id' => $user->getKey(),
                'number' => $participation->driver['licence_number'],
                'validity_year' => $validityYear,
                'holder' => HolderData::fromValidated($validated['holder']),
                'source' => LicenceSource::Participation,
                'verified_at' => $participation->confirmed_at,
                'verified_by' => self::confirmedBy($participation),
            ]);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'number' => __('This licence is already registered for :year. Contact the organizer.', ['year' => $validityYear]),
            ]);
        }

        Entitlement::linkTo($licence);

        return $licence;
    }

    /**
     * The participations the User can turn into a Licence: for each licence number, the latest confirmed participation
     * linked to the User, when no Licence with its number exists for its year or a later one, e.g. already added, or
     * emitted since from a renewal.
     *
     * @return Collection<int, Participant>
     */
    public static function candidates(User $user): Collection
    {
        return Participant::query()
            ->registered()
            ->confirmed()
            ->where(fn (Builder $query) => $query->where('claimed_by', $user->getKey())->orWhere('added_by', $user->getKey()))
            ->whereIn('licence_type', array_map(fn (DriverLicence $category) => $category->value, self::TRANSFERABLE_CATEGORIES))
            ->with('championship')
            ->get()
            ->sortByDesc(fn (Participant $participant) => [$participant->championship->start_at, $participant->confirmed_at])
            ->unique('driver_licence')
            ->filter(fn (Participant $participant) => filled($participant->driver['licence_number'] ?? null))
            ->reject(fn (Participant $participant) => Licence::query()
                ->where('number_hash', $participant->driver_licence)
                ->where('validity_year', '>=', self::validityYear($participant))
                ->exists())
            ->values()
            ->toBase();
    }

    /**
     * The participation to turn into a Licence for the driver of the given one, null when there is none.
     */
    public static function participationFor(User $user, Participant $participant): ?Participant
    {
        return self::candidates($user)->firstWhere('driver_licence', $participant->driver_licence);
    }

    /**
     * The year the Licence is valid for: the one of the participation's championship.
     */
    public static function validityYear(Participant $participation): int
    {
        return $participation->championship->start_at->year;
    }

    /**
     * What the transfer starts from: the championship's provider, or the only one offered; the Licence Type when a single
     * active one of that provider matches the participation's licence category; the holder as registered.
     *
     * @return array{provider: ?string, licence_type: ?string, driver_only: bool, holder: array<string, mixed>}
     */
    public static function defaults(Participant $participation): array
    {
        $provider = $participation->championship->licences->provider;

        if ($provider === null) {
            $providers = LicenceCatalogue::providers();
            $provider = count($providers) === 1 ? $providers[0] : null;
        }

        $matching = $provider === null ? collect() : self::matchingTypes($participation, $provider);

        $driver = $participation->driver ?? [];
        $sex = $driver['sex'] ?? null;

        return [
            'provider' => $provider?->value,
            'licence_type' => $matching->count() === 1 ? $matching->sole()->code : null,
            'driver_only' => false,
            'holder' => [
                'first_name' => $driver['first_name'] ?? null,
                'last_name' => $driver['last_name'] ?? null,
                'sex' => $sex instanceof Sex ? $sex->value : $sex,
                'birth_date' => $driver['birth_date'] ?? null,
                'birth_place' => $driver['birth_place'] ?? null,
                'fiscal_code' => $driver['fiscal_code'] ?? null,
                'nationality' => $driver['nationality'] ?? null,
                'email' => $driver['email'] ?? null,
                'phone' => $driver['phone'] ?? null,
                'residence_address' => [
                    'address' => $driver['residence_address']['address'] ?? null,
                    'city' => $driver['residence_address']['city'] ?? null,
                    'province' => $driver['residence_address']['province'] ?? null,
                    'postal_code' => $driver['residence_address']['postal_code'] ?? null,
                ],
            ],
        ];
    }

    /**
     * The active Licence Types of the provider counting as the participation's licence category in race registration.
     *
     * @return Collection<int, LicenceTypeData>
     */
    private static function matchingTypes(Participant $participation, LicenceProvider $provider): Collection
    {
        return LicenceCatalogue::types($provider)
            ->where('role', LicenceRole::Driver)
            ->filter(fn (LicenceTypeData $type) => $type->raceLicenceCategory === $participation->licence_type)
            ->values();
    }

    /**
     * The organizer who confirmed the participation, from its activity log; null when not recorded.
     */
    private static function confirmedBy(Participant $participation): ?int
    {
        $confirmation = Activity::query()
            ->where('subject_type', $participation->getMorphClass())
            ->where('subject_id', $participation->getKey())
            ->where('causer_type', (new User)->getMorphClass())
            ->latest('id')
            ->get()
            ->first(fn (Activity $activity) => filled(data_get($activity->attribute_changes, 'attributes.confirmed_at')));

        return $confirmation?->causer_id === null ? null : (int) $confirmation->causer_id;
    }
}

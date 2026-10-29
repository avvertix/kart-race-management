<?php

declare(strict_types=1);

namespace App\Support;

use App\Data\LicenceTypeData;
use App\Enums\LicenceProvider;
use App\Enums\LicenceRole;
use App\Models\DriverLicence;
use Illuminate\Support\Collection;

/**
 * The Licence Types of each Licence Provider, as listed in config/licences.php.
 */
final class LicenceCatalogue
{
    /**
     * The providers with at least one active Licence Type.
     *
     * @return array<int, LicenceProvider>
     */
    public static function providers(): array
    {
        return array_values(array_filter(
            LicenceProvider::cases(),
            fn (LicenceProvider $provider) => self::types($provider)->isNotEmpty(),
        ));
    }

    /**
     * The Licence Types of the provider, in the order they are listed. Inactive ones only when asked for.
     *
     * @return Collection<int, LicenceTypeData>
     */
    public static function types(LicenceProvider $provider, bool $withInactive = false): Collection
    {
        /** @var array<string, array{name: string, role: LicenceRole, race_licence_category?: ?DriverLicence, active?: bool}> $types */
        $types = config("licences.types.{$provider->value}", []);

        return collect($types)
            ->map(fn (array $type, string $code) => new LicenceTypeData(
                provider: $provider,
                code: $code,
                name: $type['name'],
                role: $type['role'],
                raceLicenceCategory: $type['race_licence_category'] ?? null,
                active: $type['active'] ?? true,
            ))
            ->filter(fn (LicenceTypeData $type) => $withInactive || $type->active)
            ->values();
    }

    /**
     * The Licence Type with the given code, also when inactive, so existing Licences keep their name.
     */
    public static function find(?LicenceProvider $provider, ?string $code): ?LicenceTypeData
    {
        if ($provider === null || $code === null) {
            return null;
        }

        return self::types($provider, withInactive: true)->firstWhere('code', $code);
    }

    /**
     * The Licence Types that can be picked for the provider: the active ones, plus the one already picked.
     *
     * @return Collection<int, LicenceTypeData>
     */
    public static function selectableTypes(LicenceProvider $provider, ?string $current = null): Collection
    {
        return self::types($provider, withInactive: true)
            ->filter(fn (LicenceTypeData $type) => $type->active || $type->code === $current)
            ->values();
    }

    /**
     * The codes of the Licence Types that can be picked for the provider.
     *
     * @return array<int, string>
     */
    public static function selectableCodes(LicenceProvider $provider, ?string $current = null): array
    {
        return self::selectableTypes($provider, $current)->pluck('code')->all();
    }
}

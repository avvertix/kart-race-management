<?php

declare(strict_types=1);

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Organizer = 'organizer';
    case RaceManager = 'racemanager';
    case TireAgent = 'tireagent';
    case Timekeeper = 'timekeeper';
    case Driver = 'driver';

    /**
     * The roles as a value => label map.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $role) => [$role->value => $role->label()])
            ->all();
    }

    /**
     * The human readable name of the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Organizer => 'Organizer',
            self::RaceManager => 'Race manager',
            self::TireAgent => 'Tire responsible',
            self::Timekeeper => 'Timekeeper',
            self::Driver => 'Driver',
        };
    }

    /**
     * A short description of the role.
     */
    public function description(): string
    {
        return match ($this) {
            self::Admin => 'Platform administrator',
            self::Organizer => 'Race and Championship organizer',
            self::RaceManager => 'Responsible of the race',
            self::TireAgent => 'Responsible of tire management',
            self::Timekeeper => 'Time-keeping service',
            self::Driver => 'Drivers that manage their participation, licences and enrollments',
        };
    }

    /**
     * The permissions granted to the role.
     *
     * @return list<string>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Admin => [
                '*',
            ],
            self::Organizer => [
                'championship:list',
                'championship:create',
                'championship:update',
                'championship:delete',
                'category:list',
                'category:create',
                'category:update',
                'category:delete',
                'bonus:list',
                'bonus:create',
                'bonus:update',
                'bonus:delete',
                'race:list',
                'race:create',
                'race:update',
                'race:delete',
                'participant:list',
                'participant:view',
                'participant:create',
                'participant:update',
                'participant:delete',
                'tire:list',
                'tire:view',
                'tire:create',
                'tire:update',
                'transponder:view',
                'transponder:create',
                'transponder:update',
                'payment:view',
                'communication:list',
                'communication:view',
                'communication:create',
                'communication:update',
                'communication:delete',
                'results:list',
                'results:view',
                'results:create',
                'results:update',
                'results:delete',
                'licence-type:list',
                'licence-type:view',
                'licence-type:create',
                'licence-type:update',
                'licence-type:delete',
                'product:list',
                'product:view',
                'product:create',
                'product:update',
                'product:delete',
                'licence-request:list',
                'licence-request:view',
                'licence-request:update',
                'licence:list',
                'licence:view',
                'licence:update',
                'order:list',
                'order:view',
                'order:update',
                'enrollment:list',
                'enrollment:view',
                'enrollment:create',
                'enrollment:update',
                'enrollment:delete',
                'promo-code:list',
                'promo-code:view',
                'promo-code:create',
                'promo-code:update',
                'promo-code:delete',
                'promo-code:claim',
            ],
            self::RaceManager => [
                'championship:list',
                'category:list',
                'race:list',
                'bonus:list',
                'participant:list',
                'participant:view',
                'participant:create',
                'participant:update',
                'participant:delete',
                'tire:list',
                'tire:view',
                'tire:create',
                'tire:update',
                'transponder:list',
                'transponder:view',
                'transponder:create',
                'transponder:update',
                'transponder:delete',
                'payment:view',
                'communication:list',
                'communication:view',
                'communication:create',
                'communication:update',
                'communication:delete',
                'results:list',
                'results:view',
                'results:create',
                'results:update',
                'results:delete',
            ],
            self::TireAgent => [
                'championship:list',
                'category:list',
                'race:list',
                'participant:list',
                'participant:view',
                'tire:list',
                'tire:view',
                'tire:create',
                'tire:update',
            ],
            self::Timekeeper => [
                'championship:list',
                'category:list',
                'race:list',
                'participant:list',
                'participant:view',
                'transponder:list',
                'transponder:view',
                'transponder:create',
                'transponder:update',
                'transponder:delete',
                'results:list',
                'results:view',
                'results:create',
                'results:update',
                'results:delete',
                'orbits-backup:view',
                'orbits-backup:create',
                'orbits-backup:update',
            ],
            self::Driver => [
                'drivers:view',
                'drivers:create',
                'drivers:update',
                'drivers:delete',
                'licence-request:create',
                'licence-request:view',
                'licence-request:update',
                'licence:create',
                'licence:view',
                'licence:update',
                'enrollment:create',
                'enrollment:view',
                'enrollment:update',
                'order:view',
                'promo-code:claim',
            ],
        };
    }
}

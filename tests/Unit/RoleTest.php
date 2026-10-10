<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\Role;
use PHPUnit\Framework\TestCase;

class RoleTest extends TestCase
{
    public function test_role_values_are_stable()
    {
        $this->assertEquals(
            ['admin', 'organizer', 'racemanager', 'tireagent', 'timekeeper', 'driver'],
            array_column(Role::cases(), 'value'),
        );
    }

    public function test_options_map_values_to_labels()
    {
        $this->assertEquals([
            'admin' => 'Administrator',
            'organizer' => 'Organizer',
            'racemanager' => 'Race manager',
            'tireagent' => 'Tire responsible',
            'timekeeper' => 'Timekeeper',
            'driver' => 'Driver',
        ], Role::options());
    }

    public function test_every_role_has_permissions_and_description()
    {
        foreach (Role::cases() as $role) {
            $this->assertNotEmpty($role->permissions(), "{$role->value} has no permissions");
            $this->assertNotEmpty($role->description(), "{$role->value} has no description");
        }
    }

    public function test_admin_has_wildcard_permission()
    {
        $this->assertEquals(['*'], Role::Admin->permissions());
    }
}

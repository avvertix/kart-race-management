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

    public function test_organizer_manages_licences_products_orders_enrollments_and_promo_codes(): void
    {
        $this->assertEquals([
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
        ], $this->licencePermissions(Role::Organizer));
    }

    public function test_driver_handles_own_licences_enrollments_orders_and_promo_codes(): void
    {
        $this->assertEquals([
            'licence-request:create',
            'licence-request:view',
            'licence-request:update',
            'licence:create',
            'licence:view',
            'licence:update',
            'licence:delete',
            'enrollment:create',
            'enrollment:view',
            'enrollment:update',
            'order:create',
            'order:view',
            'promo-code:claim',
        ], $this->licencePermissions(Role::Driver));
    }

    public function test_other_roles_have_no_licence_permissions(): void
    {
        foreach ([Role::RaceManager, Role::TireAgent, Role::Timekeeper] as $role) {
            $this->assertEmpty($this->licencePermissions($role), "{$role->value} has licence permissions");
        }
    }

    /**
     * The permissions of the role on products, licence requests, licences, orders, enrollments and promo codes.
     *
     * @return list<string>
     */
    private function licencePermissions(Role $role): array
    {
        $resources = ['product', 'licence-request', 'licence', 'order', 'enrollment', 'promo-code'];

        return array_values(array_filter(
            $role->permissions(),
            fn (string $permission) => in_array(explode(':', $permission)[0], $resources),
        ));
    }
}

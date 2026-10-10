<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Tests\TestCase;

class HasRoleTest extends TestCase
{
    public function test_role_can_be_retrieved()
    {
        $user = User::make([
            'role' => 'admin',
        ]);

        $this->assertSame(Role::Admin, $user->userRole());
        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->hasRole(Role::Admin));
        $this->assertTrue($user->isAdmin());
        $this->assertEquals(['*'], $user->permissions());
        $this->assertTrue($user->hasPermission('test'));
    }

    public function test_null_role_handled()
    {
        $user = User::make([
            'role' => null,
        ]);

        $this->assertNull($user->userRole());
        $this->assertFalse($user->hasRole('admin'));
        $this->assertFalse($user->isAdmin());
        $this->assertEquals([], $user->permissions());
        $this->assertFalse($user->hasPermission('test'));
    }

    public function test_unknown_role_handled()
    {
        $user = User::make([
            'role' => 'not-a-role',
        ]);

        $this->assertNull($user->userRole());
        $this->assertFalse($user->hasRole('not-a-role'));
        $this->assertEquals([], $user->permissions());
        $this->assertFalse($user->hasPermission('championship:list'));
    }

    public function test_permissions_are_granted_by_role()
    {
        $user = User::make([
            'role' => 'tireagent',
        ]);

        $this->assertSame(Role::TireAgent, $user->userRole());
        $this->assertFalse($user->isAdmin());
        $this->assertTrue($user->hasPermission('tire:update'));
        $this->assertFalse($user->hasPermission('transponder:update'));
        $this->assertFalse($user->hasPermission('championship:create'));
    }

    public function test_driver_role_permissions()
    {
        $user = User::make([
            'role' => 'driver',
        ]);

        $this->assertTrue($user->hasRole(Role::Driver));
        $this->assertTrue($user->hasPermission('drivers:view'));
        $this->assertFalse($user->hasPermission('participant:list'));
    }
}

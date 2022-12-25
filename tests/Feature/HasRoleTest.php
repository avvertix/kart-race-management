<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Jetstream\Role;
use Tests\TestCase;

class HasRoleTest extends TestCase
{

    public function test_role_can_be_retrieved()
    {
        $user = User::make([
            'role' => 'admin',
        ]);

        $this->assertInstanceOf(Role::class, $user->userRole());
        $this->assertEquals('admin', $user->userRole()->key);
        $this->assertTrue($user->hasRole('admin'));
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
        $this->assertEquals([], $user->permissions());
        $this->assertFalse($user->hasPermission('test'));
    }
}

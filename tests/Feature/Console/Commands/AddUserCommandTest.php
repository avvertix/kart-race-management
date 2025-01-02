<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddUserCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_users_can_register()
    {
        $this->artisan('user:add', [
            '--email' => 'test@local.host',
        ])
            ->expectsQuestion('Please specify an 8 character password for the administrator', 'password')
            ->assertSuccessful()
            ->expectsOutputToContain('User test@local.host created');

        $user = User::where('email', 'test@local.host')->first();

        $this->assertNotNull($user);
        $this->assertEquals('racemanager', $user->role);
    }

    public function test_new_users_can_register_with_role()
    {
        $this->artisan('user:add', [
            '--email' => 'test@local.host',
            '--role' => 'admin',
        ])
            ->expectsQuestion('Please specify an 8 character password for the administrator', 'password')
            ->assertSuccessful()
            ->expectsOutputToContain('User test@local.host created');

        $user = User::where('email', 'test@local.host')->first();

        $this->assertNotNull($user);
        $this->assertEquals('admin', $user->role);
    }

    public function test_user_creation_requires_email()
    {
        $this->artisan('user:add', ['--password' => 'password'])
            ->assertFailed()
            ->expectsOutputToContain('The email field is required');

    }

    public function test_user_creation_requires_existing_role()
    {
        $this->artisan('user:add', [
            '--email' => 'test@local.host',
            '--role' => 'unknown',
            '--password' => 'password',
        ])
            ->assertFailed()
            ->expectsOutputToContain('The role must be a valid role.');

    }
}

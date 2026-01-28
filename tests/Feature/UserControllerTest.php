<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_listing_users_requires_login(): void
    {
        $response = $this->get(route('users.index'));

        $response->assertRedirectToRoute('login');
    }

    public function test_listing_users_requires_admin_role(): void
    {
        $user = User::factory()->organizer()->create();

        $response = $this->actingAs($user)->get(route('users.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_list_users(): void
    {
        $admin = User::factory()->admin()->create();

        User::factory()->count(3)->create();

        $response = $this->actingAs($admin)->get(route('users.index'));

        $response->assertSuccessful();
        $response->assertViewIs('user.index');
        $response->assertViewHas('users');
    }

    public function test_admin_can_search_users_by_name(): void
    {
        $admin = User::factory()->admin()->create();

        $user1 = User::factory()->create(['name' => 'John Doe']);
        $user2 = User::factory()->create(['name' => 'Jane Smith']);

        $response = $this->actingAs($admin)->get(route('users.index', ['search' => 'John']));

        $response->assertSuccessful();
        $response->assertSee('John Doe');
        $response->assertDontSee('Jane Smith');
    }

    public function test_admin_can_search_users_by_email(): void
    {
        $admin = User::factory()->admin()->create();

        $user1 = User::factory()->create(['email' => 'john@example.com']);
        $user2 = User::factory()->create(['email' => 'jane@example.com']);

        $response = $this->actingAs($admin)->get(route('users.index', ['search' => 'john@']));

        $response->assertSuccessful();
        $response->assertSee('john@example.com');
        $response->assertDontSee('jane@example.com');
    }

    public function test_creating_user_requires_login(): void
    {
        $response = $this->get(route('users.create'));

        $response->assertRedirectToRoute('login');
    }

    public function test_creating_user_requires_admin_role(): void
    {
        $user = User::factory()->organizer()->create();

        $response = $this->actingAs($user)->get(route('users.create'));

        $response->assertForbidden();
    }

    public function test_admin_can_view_create_user_form(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('users.create'));

        $response->assertSuccessful();
        $response->assertViewIs('user.create');
    }

    public function test_admin_can_create_user(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->from(route('users.create'))
            ->post(route('users.store'), [
                'name' => 'New User',
                'email' => 'newuser@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'organizer',
            ]);

        $response->assertRedirectToRoute('users.index');
        $response->assertSessionHas('flash.banner');

        $this->assertDatabaseHas('users', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'role' => 'organizer',
        ]);
    }

    public function test_creating_user_requires_valid_email(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->from(route('users.create'))
            ->post(route('users.store'), [
                'name' => 'New User',
                'email' => 'invalid-email',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'organizer',
            ]);

        $response->assertRedirect(route('users.create'));
        $response->assertSessionHasErrors('email');
    }

    public function test_creating_user_requires_unique_email(): void
    {
        $admin = User::factory()->admin()->create();

        $existingUser = User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->actingAs($admin)
            ->from(route('users.create'))
            ->post(route('users.store'), [
                'name' => 'New User',
                'email' => 'existing@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'organizer',
            ]);

        $response->assertRedirect(route('users.create'));
        $response->assertSessionHasErrors('email');
    }

    public function test_creating_user_requires_password_confirmation(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->from(route('users.create'))
            ->post(route('users.store'), [
                'name' => 'New User',
                'email' => 'newuser@example.com',
                'password' => 'password123',
                'password_confirmation' => 'different',
                'role' => 'organizer',
            ]);

        $response->assertRedirect(route('users.create'));
        $response->assertSessionHasErrors('password');
    }

    public function test_editing_user_requires_login(): void
    {
        $user = User::factory()->create();

        $response = $this->get(route('users.edit', $user));

        $response->assertRedirectToRoute('login');
    }

    public function test_editing_user_requires_admin_role(): void
    {
        $user = User::factory()->organizer()->create();
        $otherUser = User::factory()->create();

        $response = $this->actingAs($user)->get(route('users.edit', $otherUser));

        $response->assertForbidden();
    }

    public function test_admin_can_view_edit_user_form(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($admin)->get(route('users.edit', $user));

        $response->assertSuccessful();
        $response->assertViewIs('user.edit');
        $response->assertViewHas('user', $user);
    }

    public function test_admin_can_update_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($admin)
            ->from(route('users.edit', $user))
            ->put(route('users.update', $user), [
                'name' => 'Updated Name',
                'email' => 'updated@example.com',
                'role' => 'racemanager',
            ]);

        $response->assertRedirectToRoute('users.index');
        $response->assertSessionHas('flash.banner');

        $user->refresh();

        $this->assertEquals('Updated Name', $user->name);
        $this->assertEquals('updated@example.com', $user->email);
        $this->assertEquals('racemanager', $user->role);
    }

    public function test_updating_user_preserves_password(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $originalPassword = $user->password;

        $response = $this->actingAs($admin)
            ->from(route('users.edit', $user))
            ->put(route('users.update', $user), [
                'name' => 'Updated Name',
                'email' => $user->email,
                'role' => $user->role,
            ]);

        $response->assertRedirectToRoute('users.index');

        $user->refresh();

        $this->assertEquals($originalPassword, $user->password);
    }

    public function test_deleting_user_requires_login(): void
    {
        $user = User::factory()->create();

        $response = $this->delete(route('users.destroy', $user));

        $response->assertRedirectToRoute('login');
    }

    public function test_deleting_user_requires_admin_role(): void
    {
        $user = User::factory()->organizer()->create();
        $otherUser = User::factory()->create();

        $response = $this->actingAs($user)->delete(route('users.destroy', $otherUser));

        $response->assertForbidden();
    }

    public function test_admin_can_delete_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($admin)
            ->from(route('users.edit', $user))
            ->delete(route('users.destroy', $user));

        $response->assertRedirectToRoute('users.index');
        $response->assertSessionHas('flash.banner');

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }

    public function test_admin_cannot_delete_themselves(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->from(route('users.edit', $admin))
            ->delete(route('users.destroy', $admin));

        $response->assertRedirectToRoute('users.edit', $admin);

        $response->assertSessionHas('flash.banner', __('You cannot delete your own account.'));

        $this->assertNotNull($admin->fresh());
    }

    public function test_admin_can_delete_admin_when_multiple_admins_exist(): void
    {
        $admin1 = User::factory()->admin()->create();
        $admin2 = User::factory()->admin()->create();
        $admin3 = User::factory()->admin()->create();

        $response = $this->actingAs($admin1)
            ->from(route('users.edit', $admin2))
            ->delete(route('users.destroy', $admin2));

        $response->assertRedirectToRoute('users.index');
        $response->assertSessionHas('flash.banner');

        $this->assertDatabaseMissing('users', [
            'id' => $admin2->id,
        ]);

        // Two admins remaining (admin1 and admin3)
        $this->assertEquals(2, User::where('role', 'admin')->count());
    }

    public function test_non_admin_users_receive_forbidden_for_all_routes(): void
    {
        $organizer = User::factory()->organizer()->create();
        $racemanager = User::factory()->racemanager()->create();
        $tireagent = User::factory()->tireagent()->create();
        $timekeeper = User::factory()->timekeeper()->create();

        $targetUser = User::factory()->create();

        $nonAdminUsers = [$organizer, $racemanager, $tireagent, $timekeeper];

        foreach ($nonAdminUsers as $user) {
            $this->actingAs($user)->get(route('users.index'))->assertForbidden();
            $this->actingAs($user)->get(route('users.create'))->assertForbidden();
            $this->actingAs($user)->post(route('users.store'))->assertForbidden();
            $this->actingAs($user)->get(route('users.edit', $targetUser))->assertForbidden();
            $this->actingAs($user)->put(route('users.update', $targetUser))->assertForbidden();
            $this->actingAs($user)->delete(route('users.destroy', $targetUser))->assertForbidden();
        }
    }
}

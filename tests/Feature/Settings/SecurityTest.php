<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Livewire\Settings\Security;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_security_settings_page_can_be_rendered()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('security.edit'))
            ->assertOk()
            ->assertSee('Two-factor authentication')
            ->assertSee('Enable 2FA')
            ->assertDontSee('No passkeys yet');
    }

    public function test_security_settings_page_requires_password_confirmation()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('security.edit'))
            ->assertRedirect(route('password.confirm'));
    }

    public function test_security_settings_page_requires_verified_email()
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('security.edit'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_security_settings_page_renders_without_two_factor_when_feature_is_disabled()
    {
        config(['fortify.features' => []]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get(route('security.edit'))
            ->assertOk()
            ->assertDontSee('Enable 2FA');
    }

    public function test_passkeys_are_not_available_when_the_feature_is_disabled()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Security::class)
            ->assertSet('canManagePasskeys', false)
            ->assertSet('passkeys', []);

        $this->assertFalse(Route::has('well-known.passkeys'));
    }

    public function test_two_factor_authentication_disabled_when_confirmation_abandoned_between_requests()
    {
        $user = User::factory()->create();

        $user->forceFill([
            'two_factor_secret' => encrypt('test-secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
            'two_factor_confirmed_at' => null,
        ])->save();

        Livewire::actingAs($user)
            ->test(Security::class)
            ->assertSet('twoFactorEnabled', false);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
        ]);
    }

    public function test_two_factor_authentication_can_be_enabled()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Security::class)
            ->call('enable')
            ->assertSet('showModal', true)
            ->assertNotSet('manualSetupKey', '');

        $this->assertNotNull($user->fresh()->two_factor_secret);
    }

    public function test_two_factor_authentication_can_be_disabled()
    {
        $user = User::factory()->create();

        $user->forceFill([
            'two_factor_secret' => encrypt('test-secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
            'two_factor_confirmed_at' => now(),
        ])->save();

        Livewire::actingAs($user)
            ->test(Security::class)
            ->assertSet('twoFactorEnabled', true)
            ->call('disable')
            ->assertSet('twoFactorEnabled', false);

        $this->assertNull($user->fresh()->two_factor_secret);
    }

    public function test_password_can_be_updated()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
        ]);

        Livewire::actingAs($user)
            ->test(Security::class)
            ->set('current_password', 'password')
            ->set('password', 'new-Password1!')
            ->set('password_confirmation', 'new-Password1!')
            ->call('updatePassword')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('new-Password1!', $user->refresh()->password));
    }

    public function test_correct_password_must_be_provided_to_update_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
        ]);

        Livewire::actingAs($user)
            ->test(Security::class)
            ->set('current_password', 'wrong-password')
            ->set('password', 'new-Password1!')
            ->set('password_confirmation', 'new-Password1!')
            ->call('updatePassword')
            ->assertHasErrors(['current_password']);

        $this->assertTrue(Hash::check('password', $user->refresh()->password));
    }

    public function test_weak_password_is_rejected()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Security::class)
            ->set('current_password', 'password')
            ->set('password', 'newpassword')
            ->set('password_confirmation', 'newpassword')
            ->call('updatePassword')
            ->assertHasErrors(['password']);
    }
}

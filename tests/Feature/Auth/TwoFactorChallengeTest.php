<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class TwoFactorChallengeTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_two_factor_challenge_redirects_to_login_when_not_authenticated()
    {
        $this->get(route('two-factor.login'))->assertRedirect(route('login'));
    }

    public function test_two_factor_challenge_can_be_rendered_after_credentials_are_validated()
    {
        $user = $this->createUserWithTwoFactor();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('two-factor.login'));

        $this->get(route('two-factor.login'))->assertOk();
        $this->assertGuest();
    }

    public function test_user_can_log_in_with_a_recovery_code()
    {
        $user = $this->createUserWithTwoFactor();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->post(route('two-factor.login.store'), ['recovery_code' => 'code1'])
            ->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_recovery_code_does_not_authenticate()
    {
        $user = $this->createUserWithTwoFactor();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->post(route('two-factor.login.store'), ['recovery_code' => 'invalid'])
            ->assertSessionHasErrors('recovery_code');

        $this->assertGuest();
    }

    private function createUserWithTwoFactor(): User
    {
        $user = User::factory()->create();

        $user->forceFill([
            'two_factor_secret' => encrypt('test-secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['code1', 'code2'])),
            'two_factor_confirmed_at' => now(),
        ])->save();

        return $user;
    }
}

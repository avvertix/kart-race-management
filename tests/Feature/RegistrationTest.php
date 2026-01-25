<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Laravel\Jetstream\Jetstream;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register()
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();

        $this->assertEquals('driver', $user->role);
    }

    public function test_honeypot_blocks_bot_registration()
    {
        $response = $this
            ->from('/register')
            ->post('/register', [
                'name' => 'Bot User',
                'email' => 'bot@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
                'website' => 'https://spam-site.com',
            ]);

        $this->assertGuest();
        $response->assertRedirect('/register');
        $response->assertSessionHasErrors('website');

        $this->assertDatabaseMissing('users', [
            'email' => 'bot@example.com',
        ]);
    }
}

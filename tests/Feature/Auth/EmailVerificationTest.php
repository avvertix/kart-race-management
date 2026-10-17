<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Laravel\Fortify\Features;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_email_verification_screen_can_be_rendered()
    {
        if (! Features::enabled(Features::emailVerification())) {
            return $this->markTestSkipped('Email verification not enabled.');
        }

        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/email/verify');

        $response->assertStatus(200);
    }

    public function test_email_can_be_verified()
    {
        if (! Features::enabled(Features::emailVerification())) {
            return $this->markTestSkipped('Email verification not enabled.');
        }

        Event::fake();

        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    }

    public function test_email_can_not_verified_with_invalid_hash()
    {
        if (! Features::enabled(Features::emailVerification())) {
            return $this->markTestSkipped('Email verification not enabled.');
        }

        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $this->actingAs($user)->get($verificationUrl);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_unverified_users_are_redirected_to_the_email_verification_prompt()
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('appearance.edit'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_already_verified_user_visiting_verification_link_does_not_fire_event_again()
    {
        $user = User::factory()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $this->actingAs($user)->get($verificationUrl)
            ->assertRedirect(route('dashboard', absolute: false).'?verified=1');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        Event::assertNotDispatched(Verified::class);
    }
}

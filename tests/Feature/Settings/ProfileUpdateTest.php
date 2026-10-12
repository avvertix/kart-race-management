<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Livewire\Settings\DeleteUserForm;
use App\Livewire\Settings\Profile;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_profile_page_is_displayed()
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('profile.edit'))->assertOk();
    }

    public function test_guests_cannot_see_profile_page()
    {
        $this->get(route('profile.edit'))->assertRedirect(route('login'));
    }

    public function test_profile_information_can_be_updated()
    {
        Notification::fake();

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->call('updateProfileInformation')
            ->assertHasNoErrors();

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_email_verification_status_is_unchanged_when_email_address_is_unchanged()
    {
        Notification::fake();

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set('name', 'Test User')
            ->set('email', $user->email)
            ->call('updateProfileInformation')
            ->assertHasNoErrors();

        $this->assertNotNull($user->refresh()->email_verified_at);
        Notification::assertNothingSent();
    }

    public function test_email_must_be_unique()
    {
        $other = User::factory()->create();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Profile::class)
            ->set('email', $other->email)
            ->call('updateProfileInformation')
            ->assertHasErrors(['email']);
    }

    public function test_user_can_delete_their_account()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(DeleteUserForm::class)
            ->set('password', 'password')
            ->call('deleteUser')
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertNull($user->fresh());
        $this->assertFalse(auth()->check());
    }

    public function test_deleting_account_detaches_added_and_claimed_participants()
    {
        $user = User::factory()->create();
        $participant = Participant::factory()->create([
            'added_by' => $user->id,
            'claimed_by' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(DeleteUserForm::class)
            ->set('password', 'password')
            ->call('deleteUser')
            ->assertHasNoErrors();

        $this->assertNull($user->fresh());

        $participant->refresh();
        $this->assertNull($participant->added_by);
        $this->assertNull($participant->claimed_by);
    }

    public function test_correct_password_must_be_provided_to_delete_account()
    {
        $user = User::factory()->create();
        $participant = Participant::factory()->create([
            'added_by' => $user->id,
            'claimed_by' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(DeleteUserForm::class)
            ->set('password', 'wrong-password')
            ->call('deleteUser')
            ->assertHasErrors(['password']);

        $this->assertNotNull($user->fresh());
        $this->assertSame($user->id, $participant->fresh()->added_by);
    }
}

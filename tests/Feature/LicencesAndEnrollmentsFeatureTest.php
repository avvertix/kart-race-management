<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Features\LicencesAndEnrollments;
use App\Models\User;
use Laravel\Pennant\Feature;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class LicencesAndEnrollmentsFeatureTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_pages_do_not_exist_until_an_admin_enables_the_user(): void
    {
        $admin = User::factory()->admin()->create();
        $organizer = User::factory()->organizer()->create();

        $this->actingAs($organizer)->get(route('licence-request-reviews.index'))->assertNotFound();
        $this->actingAs($organizer)->get(route('my-licences.index'))->assertNotFound();
        $this->actingAs($organizer)->get(route('dashboard'))->assertDontSee(route('licence-request-reviews.index'));

        $this->actingAs($admin)
            ->get(route('users.edit', $organizer))
            ->assertSee('Early access to licences and enrollments');

        $this->actingAs($admin)
            ->put(route('users.update', $organizer), [
                'name' => $organizer->name,
                'email' => $organizer->email,
                'role' => $organizer->role,
                'licences_and_enrollments' => '1',
            ])
            ->assertRedirectToRoute('users.index');

        $this->assertTrue(Feature::for($organizer)->active(LicencesAndEnrollments::class));

        $this->actingAs($organizer)->get(route('licence-request-reviews.index'))->assertSuccessful();
        $this->actingAs($organizer)->get(route('dashboard'))->assertSee(route('licence-request-reviews.index'));

        $this->actingAs($admin)
            ->put(route('users.update', $organizer), [
                'name' => $organizer->name,
                'email' => $organizer->email,
                'role' => $organizer->role,
                'licences_and_enrollments' => '0',
            ]);

        Feature::flushCache();

        $this->actingAs($organizer)->get(route('licence-request-reviews.index'))->assertNotFound();
    }

    public function test_the_feature_keeps_the_values_stored_under_its_former_name(): void
    {
        $organizer = User::factory()->organizer()->create();

        Feature::for($organizer)->activate(LicencesAndEnrollments::class);

        $this->assertDatabaseHas('features', ['name' => 'App\Features\LicencesAndProducts', 'value' => 'true']);
    }

    public function test_admins_always_have_the_pages_and_nothing_is_sold(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('licence-request-reviews.index'))->assertSuccessful();
        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertSee(route('licence-request-reviews.index'))
            ->assertDontSee(url('products'))
            ->assertDontSee(url('orders'));
        $this->actingAs($admin)->get('products')->assertNotFound();
    }
}

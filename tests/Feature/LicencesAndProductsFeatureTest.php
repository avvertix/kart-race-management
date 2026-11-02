<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Features\LicencesAndProducts;
use App\Models\User;
use Laravel\Pennant\Feature;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class LicencesAndProductsFeatureTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_pages_do_not_exist_until_an_admin_enables_the_user(): void
    {
        $admin = User::factory()->admin()->create();
        $organizer = User::factory()->organizer()->create();

        $this->get(route('public.products.index'))->assertNotFound();

        $this->actingAs($organizer)->get(route('licence-request-reviews.index'))->assertNotFound();
        $this->actingAs($organizer)->get(route('my-licences.index'))->assertNotFound();
        $this->actingAs($organizer)->get(route('dashboard'))->assertDontSee(route('products.index'));

        $this->actingAs($admin)
            ->get(route('users.edit', $organizer))
            ->assertSee('Early access to licences and products');

        $this->actingAs($admin)
            ->put(route('users.update', $organizer), [
                'name' => $organizer->name,
                'email' => $organizer->email,
                'role' => $organizer->role,
                'licences_and_products' => '1',
            ])
            ->assertRedirectToRoute('users.index');

        $this->assertTrue(Feature::for($organizer)->active(LicencesAndProducts::class));

        $this->actingAs($organizer)->get(route('licence-request-reviews.index'))->assertSuccessful();
        $this->actingAs($organizer)->get(route('dashboard'))->assertSee(route('products.index'));

        $this->actingAs($admin)
            ->put(route('users.update', $organizer), [
                'name' => $organizer->name,
                'email' => $organizer->email,
                'role' => $organizer->role,
                'licences_and_products' => '0',
            ]);

        Feature::flushCache();

        $this->actingAs($organizer)->get(route('licence-request-reviews.index'))->assertNotFound();
    }

    public function test_admins_always_have_the_pages(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('products.index'))->assertSuccessful();
        $this->actingAs($admin)->get(route('dashboard'))->assertSee(route('products.index'));
    }
}

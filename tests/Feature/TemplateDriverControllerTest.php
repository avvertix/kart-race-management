<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\TemplateDriver;
use App\Models\User;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class TemplateDriverControllerTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_listing_templates_requires_login(): void
    {
        $response = $this->get(route('drivers.index'));

        $response->assertRedirectToRoute('login');
    }

    public function test_user_can_list_their_templates(): void
    {
        $user = User::factory()->create();

        TemplateDriver::factory()->count(3)->for($user)->create();

        $response = $this->actingAs($user)->get(route('drivers.index'));

        $response->assertSuccessful();
        $response->assertViewIs('template-driver.index');
        $response->assertViewHas('templates');
    }

    public function test_user_only_sees_their_own_templates(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownTemplate = TemplateDriver::factory()->for($user)->create(['name' => 'My Template']);
        $otherTemplate = TemplateDriver::factory()->for($otherUser)->create(['name' => 'Other Template']);

        $response = $this->actingAs($user)->get(route('drivers.index'));

        $response->assertSuccessful();
        $response->assertSee('My Template');
        $response->assertDontSee('Other Template');
    }

    public function test_creating_template_requires_login(): void
    {
        $response = $this->get(route('drivers.create'));

        $response->assertRedirectToRoute('login');
    }

    public function test_user_can_view_create_form(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('drivers.create'));

        $response->assertSuccessful();
        $response->assertViewIs('template-driver.create');
    }

    public function test_user_can_create_template(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('drivers.create'))
            ->post(route('drivers.store'), [
                'name' => 'Test Template',
                'bib' => 42,
                'driver_first_name' => 'John',
                'driver_last_name' => 'Doe',
                'driver_email' => 'john@example.com',
                'driver_phone' => '+39123456789',
                'driver_nationality' => 'Italy',
            ]);

        $response->assertRedirectToRoute('drivers.index');
        $response->assertSessionHas('flash.banner');

        $this->assertDatabaseHas('template_drivers', [
            'user_id' => $user->id,
            'name' => 'Test Template',
            'bib' => 42,
        ]);

        $template = TemplateDriver::where('user_id', $user->id)->first();
        $this->assertEquals('John', $template->driver['first_name']);
        $this->assertEquals('Doe', $template->driver['last_name']);
        $this->assertEquals(42, $template->bib);
    }

    public function test_creating_template_without_name(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('drivers.create'))
            ->post(route('drivers.store'), [
                'bib' => 1,
                'driver_first_name' => 'John',
                'driver_last_name' => 'Doe',
            ]);

        $response->assertRedirectToRoute('drivers.index');

        $this->assertDatabaseHas('template_drivers', [
            'user_id' => $user->id,
            'name' => null,
            'bib' => 1,
        ]);
    }

    public function test_creating_template_requires_driver_names(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('drivers.create'))
            ->post(route('drivers.store'), [
                'name' => 'Test Template',
                'bib' => 1,
            ]);

        $response->assertRedirect(route('drivers.create'));
        $response->assertSessionHasErrors(['driver_first_name', 'driver_last_name']);
    }

    public function test_creating_template_requires_bib(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('drivers.create'))
            ->post(route('drivers.store'), [
                'name' => 'Test Template',
                'driver_first_name' => 'John',
                'driver_last_name' => 'Doe',
            ]);

        $response->assertRedirect(route('drivers.create'));
        $response->assertSessionHasErrors('bib');
    }

    public function test_creating_template_requires_nonzero_bib(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('drivers.create'))
            ->post(route('drivers.store'), [
                'name' => 'Test Template',
                'bib' => 0,
                'driver_first_name' => 'John',
                'driver_last_name' => 'Doe',
            ]);

        $response->assertRedirect(route('drivers.create'));
        $response->assertSessionHasErrors('bib');
    }

    public function test_editing_template_requires_login(): void
    {
        $template = TemplateDriver::factory()->create();

        $response = $this->get(route('drivers.edit', $template));

        $response->assertRedirectToRoute('login');
    }

    public function test_user_can_edit_their_own_template(): void
    {
        $user = User::factory()->create();
        $template = TemplateDriver::factory()->for($user)->create();

        $response = $this->actingAs($user)->get(route('drivers.edit', $template));

        $response->assertSuccessful();
        $response->assertViewIs('template-driver.edit');
        $response->assertViewHas('template', $template);
    }

    public function test_user_cannot_edit_others_template(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $template = TemplateDriver::factory()->for($otherUser)->create();

        $response = $this->actingAs($user)->get(route('drivers.edit', $template));

        $response->assertForbidden();
    }

    public function test_user_can_update_their_own_template(): void
    {
        $user = User::factory()->create();
        $template = TemplateDriver::factory()->for($user)->create();

        $response = $this->actingAs($user)
            ->from(route('drivers.edit', $template))
            ->put(route('drivers.update', $template), [
                'name' => 'Updated Template',
                'bib' => 99,
                'driver_first_name' => 'Jane',
                'driver_last_name' => 'Smith',
            ]);

        $response->assertRedirectToRoute('drivers.index');
        $response->assertSessionHas('flash.banner');

        $template->refresh();

        $this->assertEquals('Updated Template', $template->name);
        $this->assertEquals(99, $template->bib);
        $this->assertEquals('Jane', $template->driver['first_name']);
        $this->assertEquals('Smith', $template->driver['last_name']);
    }

    public function test_user_cannot_update_others_template(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $template = TemplateDriver::factory()->for($otherUser)->create();

        $response = $this->actingAs($user)
            ->put(route('drivers.update', $template), [
                'name' => 'Updated Template',
                'bib' => 99,
                'driver_first_name' => 'Jane',
                'driver_last_name' => 'Smith',
            ]);

        $response->assertForbidden();
    }

    public function test_deleting_template_requires_login(): void
    {
        $template = TemplateDriver::factory()->create();

        $response = $this->delete(route('drivers.destroy', $template));

        $response->assertRedirectToRoute('login');
    }

    public function test_user_can_delete_their_own_template(): void
    {
        $user = User::factory()->create();
        $template = TemplateDriver::factory()->for($user)->create();

        $response = $this->actingAs($user)
            ->from(route('drivers.edit', $template))
            ->delete(route('drivers.destroy', $template));

        $response->assertRedirectToRoute('drivers.index');
        $response->assertSessionHas('flash.banner');

        $this->assertDatabaseMissing('template_drivers', [
            'id' => $template->id,
        ]);
    }

    public function test_user_cannot_delete_others_template(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $template = TemplateDriver::factory()->for($otherUser)->create();

        $response = $this->actingAs($user)
            ->delete(route('drivers.destroy', $template));

        $response->assertForbidden();

        $this->assertDatabaseHas('template_drivers', [
            'id' => $template->id,
        ]);
    }

    public function test_template_stores_competitor_data(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('drivers.store'), [
                'name' => 'Template with Competitor',
                'bib' => 1,
                'driver_first_name' => 'John',
                'driver_last_name' => 'Doe',
                'competitor_first_name' => 'Jane',
                'competitor_last_name' => 'Smith',
                'competitor_email' => 'jane@example.com',
            ]);

        $response->assertRedirectToRoute('drivers.index');

        $template = TemplateDriver::where('user_id', $user->id)->first();
        $this->assertEquals('Jane', $template->competitor['first_name']);
        $this->assertEquals('Smith', $template->competitor['last_name']);
    }

    public function test_template_stores_mechanic_data(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('drivers.store'), [
                'name' => 'Template with Mechanic',
                'bib' => 1,
                'driver_first_name' => 'John',
                'driver_last_name' => 'Doe',
                'mechanic_name' => 'Mike Mechanic',
                'mechanic_licence_number' => '12345',
            ]);

        $response->assertRedirectToRoute('drivers.index');

        $template = TemplateDriver::where('user_id', $user->id)->first();
        $this->assertEquals('Mike Mechanic', $template->mechanic['name']);
        $this->assertEquals('12345', $template->mechanic['licence_number']);
    }
}

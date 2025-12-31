<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Championship;
use App\Models\ChampionshipTire;
use App\Models\User;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class CopyChampionshipCategoriesControllerTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_copy_form_requires_login(): void
    {
        $championship = Championship::factory()->create();

        $response = $this->get(route('championships.categories.copy', $championship));

        $response->assertRedirectToRoute('login');
    }

    public function test_copy_form_requires_organizer_level(): void
    {
        $user = User::factory()->racemanager()->create();
        $championship = Championship::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('championships.categories.copy', $championship));

        $response->assertForbidden();
    }

    public function test_copy_form_shown_successfully(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        // Create other championships with categories
        $sourceChampionship1 = Championship::factory()
            ->has(Category::factory()->count(2), 'categories')
            ->create(['title' => 'Source Championship 1']);

        $sourceChampionship2 = Championship::factory()
            ->has(Category::factory()->count(3), 'categories')
            ->create(['title' => 'Source Championship 2']);

        // Championship without categories should not appear
        Championship::factory()->create(['title' => 'Empty Championship']);

        $response = $this
            ->actingAs($user)
            ->get(route('championships.categories.copy', $championship));

        $response->assertSuccessful();
        $response->assertViewIs('category.copy');
        $response->assertViewHas('championship', $championship);

        $sourceChampionships = $response->viewData('sourceChampionships');
        $this->assertCount(2, $sourceChampionships);
        $this->assertTrue($sourceChampionships->contains($sourceChampionship1));
        $this->assertTrue($sourceChampionships->contains($sourceChampionship2));
    }

    public function test_copy_form_excludes_current_championship(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()
            ->has(Category::factory()->count(2), 'categories')
            ->create();

        $response = $this
            ->actingAs($user)
            ->get(route('championships.categories.copy', $championship));

        $response->assertSuccessful();

        $sourceChampionships = $response->viewData('sourceChampionships');
        $this->assertFalse($sourceChampionships->contains($championship));
    }

    public function test_copy_form_only_shows_championships_with_categories(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        // Create championships without categories
        Championship::factory()->count(3)->create();

        $response = $this
            ->actingAs($user)
            ->get(route('championships.categories.copy', $championship));

        $response->assertSuccessful();

        $sourceChampionships = $response->viewData('sourceChampionships');
        $this->assertCount(0, $sourceChampionships);
    }

    public function test_store_copy_requires_login(): void
    {
        $championship = Championship::factory()->create();
        $sourceChampionship = Championship::factory()
            ->has(Category::factory()->count(2), 'categories')
            ->create();

        $response = $this->post(route('championships.categories.store-copy', $championship), [
            'source_championship' => $sourceChampionship->id,
        ]);

        $response->assertRedirectToRoute('login');
    }

    public function test_store_copy_requires_organizer_level(): void
    {
        $user = User::factory()->racemanager()->create();
        $championship = Championship::factory()->create();
        $sourceChampionship = Championship::factory()
            ->has(Category::factory()->count(2), 'categories')
            ->create();

        $response = $this
            ->actingAs($user)
            ->post(route('championships.categories.store-copy', $championship), [
                'source_championship' => $sourceChampionship->id,
            ]);

        $response->assertForbidden();
    }

    public function test_store_copy_validates_required_source_championship(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('championships.categories.store-copy', $championship), [
                'source_championship' => '',
            ]);

        $response->assertSessionHasErrors('source_championship');
    }

    public function test_store_copy_validates_source_championship_must_be_integer(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('championships.categories.store-copy', $championship), [
                'source_championship' => 'not-an-integer',
            ]);

        $response->assertSessionHasErrors('source_championship');
    }

    public function test_store_copy_validates_source_championship_must_exist(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('championships.categories.store-copy', $championship), [
                'source_championship' => 99999,
            ]);

        $response->assertSessionHasErrors('source_championship');
    }

    public function test_store_copy_successfully_copies_categories(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        // Create source championship with categories
        $sourceChampionship = Championship::factory()->create();
        $sourceChampionship->categories()->create([
            'name' => 'Minikart',
            'code' => 'MINI',
            'description' => 'Entry level',
            'enabled' => true,
            'short_name' => 'MK',
        ]);
        $sourceChampionship->categories()->create([
            'name' => 'Junior',
            'code' => 'JR',
            'enabled' => false,
        ]);

        $this->assertCount(0, $championship->categories);

        $response = $this
            ->actingAs($user)
            ->post(route('championships.categories.store-copy', $championship), [
                'source_championship' => $sourceChampionship->id,
            ]);

        $response->assertRedirect(route('championships.categories.index', $championship));
        $response->assertSessionHas('flash.banner');

        $championship->refresh();
        $this->assertCount(2, $championship->categories);

        $copiedCategory = $championship->categories->firstWhere('code', 'MINI');
        $this->assertEquals('Minikart', $copiedCategory->name);
        $this->assertEquals('Entry level', $copiedCategory->description);
        $this->assertTrue($copiedCategory->enabled);
        $this->assertEquals('MK', $copiedCategory->short_name);
    }

    public function test_store_copy_creates_missing_tires_automatically(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        // Create source championship with category that has a tire
        $sourceChampionship = Championship::factory()->create();
        $sourceTire = $sourceChampionship->tires()->create([
            'name' => 'Bridgestone YDS',
            'code' => 'BG-YDS',
            'price' => 15000,
        ]);
        $sourceChampionship->categories()->create([
            'name' => 'Senior',
            'code' => 'SR',
            'enabled' => true,
            'championship_tire_id' => $sourceTire->id,
        ]);

        $this->assertCount(0, $championship->tires);
        $this->assertCount(0, $championship->categories);

        $response = $this
            ->actingAs($user)
            ->post(route('championships.categories.store-copy', $championship), [
                'source_championship' => $sourceChampionship->id,
            ]);

        $response->assertRedirect(route('championships.categories.index', $championship));

        $championship->refresh();

        // Tire should be auto-created
        $this->assertCount(1, $championship->tires);
        $createdTire = $championship->tires->first();
        $this->assertEquals('Bridgestone YDS', $createdTire->name);
        $this->assertEquals('BG-YDS', $createdTire->code);
        $this->assertEquals(15000, $createdTire->price);

        // Category should reference the new tire
        $copiedCategory = $championship->categories->first();
        $this->assertEquals($createdTire->id, $copiedCategory->championship_tire_id);
    }

    public function test_store_copy_reuses_existing_tires(): void
    {
        $user = User::factory()->organizer()->create();

        // Create target championship with an existing tire
        $championship = Championship::factory()->create();
        $existingTire = $championship->tires()->create([
            'name' => 'Bridgestone YDS',
            'code' => 'BG-YDS',
            'price' => 15000,
        ]);

        // Create source championship with category using same tire code
        $sourceChampionship = Championship::factory()->create();
        $sourceTire = $sourceChampionship->tires()->create([
            'name' => 'Bridgestone YDS',
            'code' => 'BG-YDS',
            'price' => 15000,
        ]);
        $sourceChampionship->categories()->create([
            'name' => 'Senior',
            'code' => 'SR',
            'enabled' => true,
            'championship_tire_id' => $sourceTire->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('championships.categories.store-copy', $championship), [
                'source_championship' => $sourceChampionship->id,
            ]);

        $response->assertRedirect(route('championships.categories.index', $championship));

        $championship->refresh();

        // Should still have only 1 tire (reused, not duplicated)
        $this->assertCount(1, $championship->tires);
        $this->assertEquals($existingTire->id, $championship->tires->first()->id);

        // Category should reference the existing tire
        $copiedCategory = $championship->categories->first();
        $this->assertEquals($existingTire->id, $copiedCategory->championship_tire_id);
    }

    public function test_store_copy_handles_multiple_categories_with_shared_tire(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        // Create source championship with multiple categories sharing one tire
        $sourceChampionship = Championship::factory()->create();
        $sourceTire = $sourceChampionship->tires()->create([
            'name' => 'Vega XH3',
            'code' => 'VG-XH3',
            'price' => 12000,
        ]);

        $sourceChampionship->categories()->create([
            'name' => 'Category A',
            'code' => 'CAT-A',
            'enabled' => true,
            'championship_tire_id' => $sourceTire->id,
        ]);

        $sourceChampionship->categories()->create([
            'name' => 'Category B',
            'code' => 'CAT-B',
            'enabled' => true,
            'championship_tire_id' => $sourceTire->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('championships.categories.store-copy', $championship), [
                'source_championship' => $sourceChampionship->id,
            ]);

        $response->assertRedirect(route('championships.categories.index', $championship));

        $championship->refresh();

        // Should create only 1 tire for both categories
        $this->assertCount(1, $championship->tires);
        $this->assertCount(2, $championship->categories);

        $createdTire = $championship->tires->first();

        // Both categories should reference the same tire
        $championship->categories->each(function ($category) use ($createdTire) {
            $this->assertEquals($createdTire->id, $category->championship_tire_id);
        });
    }

    public function test_store_copy_preserves_source_championship_data(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        $sourceChampionship = Championship::factory()->create();
        $originalCategoriesCount = $sourceChampionship->categories()->count();

        $sourceChampionship->categories()->create([
            'name' => 'Test Category',
            'code' => 'TEST',
            'enabled' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('championships.categories.store-copy', $championship), [
                'source_championship' => $sourceChampionship->id,
            ]);

        $response->assertRedirect(route('championships.categories.index', $championship));

        $sourceChampionship->refresh();

        // Source should still have the same categories
        $this->assertCount($originalCategoriesCount + 1, $sourceChampionship->categories);
    }

    public function test_store_copy_displays_success_message_with_count(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        $sourceChampionship = Championship::factory()
            ->has(Category::factory()->count(3), 'categories')
            ->create();

        $response = $this
            ->actingAs($user)
            ->post(route('championships.categories.store-copy', $championship), [
                'source_championship' => $sourceChampionship->id,
            ]);

        $response->assertRedirect(route('championships.categories.index', $championship));
        $response->assertSessionHas('flash.banner');

        $banner = session('flash.banner');
        $this->assertStringContainsString('3', $banner);
    }
}

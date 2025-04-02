<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Championship;
use App\Models\ChampionshipTire;
use App\Models\Participant;
use App\Models\User;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ChampionshipCategoryControllerTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_listing_categories_requires_login(): void
    {
        $championship = Championship::factory()->create();

        $response = $this->get(route('championships.categories.index', $championship));

        $response->assertRedirectToRoute('login');
    }

    public function test_creating_categories_requires_login(): void
    {
        $championship = Championship::factory()->create();

        $response = $this->get(route('championships.categories.create', $championship));

        $response->assertRedirectToRoute('login');
    }

    public function test_categories_can_be_listed(): void
    {
        $user = User::factory()->racemanager()->create();

        $championship = Championship::factory()
            ->has(Category::factory()->count(2))
            ->create();

        $response = $this
            ->actingAs($user)
            ->get(route('championships.categories.index', $championship));

        $response->assertSuccessful();

        $response->assertViewIs('category.index');

        $response->assertViewHas('categories', $championship->categories()->orderBy('name', 'ASC')->get());
    }

    public function test_category_creation_requires_organizer_level(): void
    {
        $user = User::factory()->racemanager()->create();

        $championship = Championship::factory()
            ->create();

        $response = $this
            ->actingAs($user)
            ->get(route('championships.categories.create', $championship));

        $response->assertForbidden();
    }

    public function test_category_creation_form_shown(): void
    {
        $user = User::factory()->organizer()->create();

        $championship = Championship::factory()
            ->has(ChampionshipTire::factory()->count(1), 'tires')
            ->create();

        $response = $this
            ->actingAs($user)
            ->get(route('championships.categories.create', $championship));

        $response->assertSuccessful();

        $response->assertViewIs('category.create');

        $response->assertViewHas('championship', $championship);

        $response->assertViewHas('tires', $championship->tires);
    }

    public function test_category_created_without_tire(): void
    {
        $user = User::factory()->organizer()->create();

        $championship = Championship::factory()
            ->create();

        $response = $this
            ->actingAs($user)
            ->from(route('championships.categories.create', $championship))
            ->post(route('championships.categories.store', $championship), [
                'name' => 'Category name',
                'short_name' => 'Alternate name',
                'enabled' => true,
            ]);

        $response->assertRedirectToRoute('championships.categories.index', $championship);

        $response->assertSessionHas('flash.banner', 'Category name created.');

        $category = Category::first();

        $this->assertInstanceOf(Category::class, $category);

        $this->assertEquals('Category name', $category->name);
        $this->assertEquals('Alternate name', $category->short_name);
        $this->assertNull($category->description);
        $this->assertNull($category->code);
        $this->assertTrue($category->enabled);
        $this->assertNull($category->tire);
    }

    public function test_category_created(): void
    {
        $user = User::factory()->organizer()->create();

        $championship = Championship::factory()
            ->has(ChampionshipTire::factory()->count(2), 'tires')
            ->create();

        $response = $this
            ->actingAs($user)
            ->from(route('championships.categories.create', $championship))
            ->post(route('championships.categories.store', $championship), [
                'name' => 'Category name',
                'short_name' => 'Alternate name',
                'enabled' => true,
                'tire' => $championship->tires()->first()->getKey(),
            ]);

        $response->assertRedirectToRoute('championships.categories.index', $championship);

        $response->assertSessionHas('flash.banner', 'Category name created.');

        $category = Category::first();

        $this->assertInstanceOf(Category::class, $category);

        $this->assertEquals('Category name', $category->name);
        $this->assertEquals('Alternate name', $category->short_name);
        $this->assertNull($category->description);
        $this->assertNull($category->code);
        $this->assertTrue($category->enabled);
        $this->assertTrue($category->tire->is($championship->tires()->first()));
    }

    public function test_category_creation_do_not_require_tire(): void
    {
        $user = User::factory()->organizer()->create();

        $championship = Championship::factory()
            ->create();

        $response = $this
            ->actingAs($user)
            ->from(route('championships.categories.create', $championship))
            ->post(route('championships.categories.store', $championship), [
                'name' => 'Category name',
                'short_name' => 'Alternate name',
                'enabled' => true,
                'tire' => '',
            ]);

        $response->assertRedirectToRoute('championships.categories.index', $championship);

        $response->assertSessionHas('flash.banner', 'Category name created.');

        $category = Category::first();

        $this->assertInstanceOf(Category::class, $category);

        $this->assertEquals('Category name', $category->name);
        $this->assertEquals('Alternate name', $category->short_name);
        $this->assertNull($category->description);
        $this->assertNull($category->code);
        $this->assertTrue($category->enabled);
        $this->assertNull($category->tire);
    }

    public function test_category_can_use_same_name_within_different_championships(): void
    {
        $user = User::factory()->organizer()->create();

        $otherChampionship = Championship::factory()
            ->create();

        $otherCategory = Category::factory()
            ->recycle($otherChampionship)
            ->create([
                'name' => 'Category name',
            ]);

        $championship = Championship::factory()
            ->has(ChampionshipTire::factory()->count(2), 'tires')
            ->create();

        $response = $this
            ->actingAs($user)
            ->from(route('championships.categories.create', $championship))
            ->post(route('championships.categories.store', $championship), [
                'name' => 'Category name',
                'short_name' => 'Alternate name',
                'enabled' => true,
                'tire' => $championship->tires()->first()->getKey(),
            ]);

        $response->assertRedirectToRoute('championships.categories.index', $championship);

        $response->assertSessionHas('flash.banner', 'Category name created.');

        $category = $championship->categories()->first();

        $this->assertInstanceOf(Category::class, $category);

        $this->assertEquals('Category name', $category->name);
        $this->assertEquals('Alternate name', $category->short_name);
        $this->assertNull($category->description);
        $this->assertNull($category->code);
        $this->assertTrue($category->enabled);
        $this->assertTrue($category->tire->is($championship->tires()->first()));
    }

    public function test_category_not_created_when_tire_not_in_championship(): void
    {
        $user = User::factory()->organizer()->create();

        $tire = ChampionshipTire::factory()->create();

        $championship = Championship::factory()
            ->create();

        $response = $this
            ->actingAs($user)
            ->from(route('championships.categories.create', $championship))
            ->post(route('championships.categories.store', $championship), [
                'name' => 'Category name',
                'short_name' => 'Alternate name',
                'enabled' => true,
                'tire' => $tire->getKey(),
            ]);

        $response->assertRedirectToRoute('championships.categories.create', $championship);

        $response->assertSessionHasErrors('tire');

        $category = Category::first();

        $this->assertNull($category);
    }

    public function test_category_not_created_when_name_above_maximum_length(): void
    {
        $user = User::factory()->organizer()->create();

        $championship = Championship::factory()
            ->create();

        $response = $this
            ->actingAs($user)
            ->from(route('championships.categories.create', $championship))
            ->post(route('championships.categories.store', $championship), [
                'name' => 'Category name '.Str::random(250),
                'short_name' => 'Alternate name',
                'enabled' => true,
            ]);

        $response->assertRedirectToRoute('championships.categories.create', $championship);

        $response->assertSessionHasErrors('name');

        $category = Category::first();

        $this->assertNull($category);
    }

    public function test_category_edit_form_shown(): void
    {
        $user = User::factory()->organizer()->create();

        $category = Category::factory()
            ->create();

        $response = $this
            ->actingAs($user)
            ->get(route('categories.edit', $category));

        $response->assertSuccessful();

        $response->assertViewIs('category.edit');

        $response->assertViewHas('category', $category);

        $response->assertViewHas('championship', $category->championship);

        $response->assertViewHas('tires', $category->championship->tires);
    }

    public function test_category_updated(): void
    {
        $user = User::factory()->organizer()->create();

        $championship = Championship::factory()
            ->create();

        $category = Category::factory()
            ->recycle($championship)
            ->withTire()
            ->create();

        $response = $this
            ->actingAs($user)
            ->from(route('categories.edit', $category))
            ->put(route('categories.update', $category), [
                'name' => 'Category name',
                'description' => 'Added description',
                'enabled' => false,
                'tire' => $category->tire->getKey(),
            ]);

        $response->assertRedirectToRoute('championships.categories.index', $category->championship);

        $response->assertSessionHas('flash.banner', 'Category name updated.');

        $updatedCategory = $category->fresh();

        $this->assertInstanceOf(Category::class, $updatedCategory);

        $this->assertEquals('Category name', $updatedCategory->name);
        $this->assertEquals('Added description', $updatedCategory->description);
        $this->assertNull($updatedCategory->short_name);
        $this->assertFalse($updatedCategory->enabled);
        $this->assertTrue($updatedCategory->tire->is($category->tire));
    }

    public function test_category_updated_tire_removed(): void
    {
        $user = User::factory()->organizer()->create();

        $championship = Championship::factory()
            ->create();

        $category = Category::factory()
            ->recycle($championship)
            ->withTire()
            ->create();

        $response = $this
            ->actingAs($user)
            ->from(route('categories.edit', $category))
            ->put(route('categories.update', $category), [
                'name' => 'Category name',
                'description' => 'Added description',
                'enabled' => false,
                'tire' => '',
            ]);

        $response->assertRedirectToRoute('championships.categories.index', $category->championship);

        $response->assertSessionHas('flash.banner', 'Category name updated.');

        $updatedCategory = $category->fresh();

        $this->assertInstanceOf(Category::class, $updatedCategory);

        $this->assertEquals('Category name', $updatedCategory->name);
        $this->assertEquals('Added description', $updatedCategory->description);
        $this->assertNull($updatedCategory->short_name);
        $this->assertFalse($updatedCategory->enabled);
        $this->assertNull($updatedCategory->tire);
    }

    public function test_category_updated_with_name_of_a_category_in_another_championship(): void
    {
        $user = User::factory()->organizer()->create();

        $otherChampionship = Championship::factory()
            ->create();

        $otherCategory = Category::factory()
            ->recycle($otherChampionship)
            ->create([
                'name' => 'Category name',
            ]);

        $championship = Championship::factory()
            ->create();

        $category = Category::factory()
            ->recycle($championship)
            ->withTire()
            ->create();

        $response = $this
            ->actingAs($user)
            ->from(route('categories.edit', $category))
            ->put(route('categories.update', $category), [
                'name' => 'Category name',
                'description' => 'Added description',
                'enabled' => false,
                'tire' => $category->tire->getKey(),
            ]);

        $response->assertRedirectToRoute('championships.categories.index', $category->championship);

        $response->assertSessionHas('flash.banner', 'Category name updated.');

        $updatedCategory = $category->fresh();

        $this->assertInstanceOf(Category::class, $updatedCategory);

        $this->assertEquals('Category name', $updatedCategory->name);
        $this->assertEquals('Added description', $updatedCategory->description);
        $this->assertNull($updatedCategory->short_name);
        $this->assertFalse($updatedCategory->enabled);
        $this->assertTrue($updatedCategory->tire->is($category->tire));
    }

    public function test_category_not_updated_when_tire_not_in_championship(): void
    {
        $user = User::factory()->organizer()->create();

        $tire = ChampionshipTire::factory()->create();

        $category = Category::factory()
            ->create();

        $response = $this
            ->actingAs($user)
            ->from(route('categories.edit', $category))
            ->put(route('categories.update', $category), [
                'name' => 'Category name',
                'description' => 'Added description',
                'enabled' => false,
                'tire' => $tire->getKey(),
            ]);

        $response->assertRedirectToRoute('categories.edit', $category);

        $response->assertSessionHasErrors('tire');
    }

    public function test_category_can_be_disabled_by_omitting_enabled(): void
    {
        $user = User::factory()->organizer()->create();

        $category = Category::factory()
            ->create();

        $response = $this
            ->actingAs($user)
            ->from(route('categories.edit', $category))
            ->put(route('categories.update', $category), [
                'name' => 'Category name',
            ]);

        $response->assertRedirectToRoute('championships.categories.index', $category->championship);

        $response->assertSessionHas('flash.banner', 'Category name updated.');

        $updatedCategory = $category->fresh();

        $this->assertInstanceOf(Category::class, $updatedCategory);

        $this->assertEquals('Category name', $updatedCategory->name);
        $this->assertNull($updatedCategory->short_name);
        $this->assertFalse($updatedCategory->enabled);
    }

    public function test_category_cannot_be_disabled_when_assigned_to_a_participant(): void
    {
        $user = User::factory()->organizer()->create();

        $category = Category::factory()
            ->create([
                'enabled' => true,
            ]);

        $existingParticipant = Participant::factory()
            ->recycle($category->championship)
            ->category($category)
            ->create();

        $response = $this
            ->actingAs($user)
            ->from(route('categories.edit', $category))
            ->put(route('categories.update', $category), [
                'name' => 'Category name',
            ]);

        $response->assertRedirectToRoute('categories.edit', $category);

        $response->assertSessionHasErrors(['enabled' => 'The category cannot be deactivated because one or more competitors are registered in it.']);

        $updatedCategory = $category->fresh();

        $this->assertInstanceOf(Category::class, $updatedCategory);

        $this->assertTrue($updatedCategory->enabled);
    }

    public function test_category_details_shown(): void
    {
        $user = User::factory()->organizer()->create();

        $category = Category::factory()
            ->create();

        $response = $this
            ->actingAs($user)
            ->get(route('categories.show', $category));

        $response->assertSuccessful();

        $response->assertViewIs('category.show');

        $response->assertViewHas('category', $category);

        $response->assertViewHas('championship', $category->championship);
    }
}

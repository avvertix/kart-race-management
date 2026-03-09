<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AwardRankingMode;
use App\Models\AwardType;
use App\Models\Category;
use App\Models\Championship;
use App\Models\ChampionshipAward;
use App\Models\Race;
use App\Models\User;
use App\Models\WildcardFilter;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class ChampionshipAwardControllerTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_listing_awards_requires_login(): void
    {
        $championship = Championship::factory()->create();

        $response = $this->get(route('championships.awards.index', $championship));

        $response->assertRedirectToRoute('login');
    }

    public function test_awards_can_be_listed(): void
    {
        $user = User::factory()->racemanager()->create();

        $championship = Championship::factory()->create();

        $category = Category::factory()->create(['championship_id' => $championship->getKey()]);

        ChampionshipAward::factory()->count(2)->create([
            'championship_id' => $championship->getKey(),
            'category_id' => $category->getKey(),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('championships.awards.index', $championship));

        $response->assertSuccessful();
        $response->assertViewIs('award.index');
        $response->assertViewHas('awards');
    }

    public function test_creating_award_requires_organizer_level(): void
    {
        $user = User::factory()->racemanager()->create();
        $championship = Championship::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('championships.awards.create', $championship));

        $response->assertForbidden();
    }

    public function test_create_category_award_form_shown(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();
        Category::factory()->create(['championship_id' => $championship->getKey()]);
        Race::factory()->create(['championship_id' => $championship->getKey()]);

        $response = $this
            ->actingAs($user)
            ->get(route('championships.awards.create', ['championship' => $championship, 'type' => 'category']));

        $response->assertSuccessful();
        $response->assertViewIs('award.create');
        $response->assertViewHas('type', AwardType::Category);
        $response->assertViewHas('categories');
        $response->assertViewHas('races');
    }

    public function test_create_overall_award_form_shown(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();
        Category::factory()->count(2)->create(['championship_id' => $championship->getKey()]);

        $response = $this
            ->actingAs($user)
            ->get(route('championships.awards.create', ['championship' => $championship, 'type' => 'overall']));

        $response->assertSuccessful();
        $response->assertViewIs('award.create');
        $response->assertViewHas('type', AwardType::Overall);
        $response->assertViewHas('categories');
    }

    public function test_category_award_created(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();
        $category = Category::factory()->create(['championship_id' => $championship->getKey()]);

        $response = $this
            ->actingAs($user)
            ->from(route('championships.awards.create', $championship))
            ->post(route('championships.awards.store', $championship), [
                'type' => 'category',
                'name' => 'Mini Championship',
                'category_id' => $category->getKey(),
                'ranking_mode' => 'all',
                'wildcard_filter' => 'all',
            ]);

        $response->assertRedirectToRoute('championships.awards.index', $championship);
        $response->assertSessionHas('flash.banner', 'Award created.');

        $award = ChampionshipAward::first();
        $this->assertInstanceOf(ChampionshipAward::class, $award);
        $this->assertEquals('Mini Championship', $award->name);
        $this->assertEquals(AwardType::Category, $award->type);
        $this->assertEquals(AwardRankingMode::All, $award->ranking_mode);
        $this->assertEquals($category->getKey(), $award->category_id);
    }

    public function test_category_award_created_with_best_n(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();
        $category = Category::factory()->create(['championship_id' => $championship->getKey()]);

        $response = $this
            ->actingAs($user)
            ->from(route('championships.awards.create', $championship))
            ->post(route('championships.awards.store', $championship), [
                'type' => 'category',
                'name' => 'Best 5 races',
                'category_id' => $category->getKey(),
                'ranking_mode' => 'best_n',
                'best_n' => 5,
                'wildcard_filter' => 'all',
            ]);

        $response->assertRedirectToRoute('championships.awards.index', $championship);

        $award = ChampionshipAward::first();
        $this->assertEquals(AwardRankingMode::BestN, $award->ranking_mode);
        $this->assertEquals(5, $award->best_n);
    }

    public function test_category_award_created_with_specific_races(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();
        $category = Category::factory()->create(['championship_id' => $championship->getKey()]);
        $race1 = Race::factory()->create(['championship_id' => $championship->getKey()]);
        $race2 = Race::factory()->create(['championship_id' => $championship->getKey()]);

        $response = $this
            ->actingAs($user)
            ->from(route('championships.awards.create', $championship))
            ->post(route('championships.awards.store', $championship), [
                'type' => 'category',
                'name' => 'Selected races',
                'category_id' => $category->getKey(),
                'ranking_mode' => 'specific',
                'race_ids' => [$race1->getKey(), $race2->getKey()],
                'wildcard_filter' => 'all',
            ]);

        $response->assertRedirectToRoute('championships.awards.index', $championship);

        $award = ChampionshipAward::first();
        $this->assertEquals(AwardRankingMode::SpecificRaces, $award->ranking_mode);
        $this->assertCount(2, $award->races);
    }

    public function test_overall_award_created(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();
        $cat1 = Category::factory()->create(['championship_id' => $championship->getKey()]);
        $cat2 = Category::factory()->create(['championship_id' => $championship->getKey()]);

        $response = $this
            ->actingAs($user)
            ->from(route('championships.awards.create', $championship))
            ->post(route('championships.awards.store', $championship), [
                'type' => 'overall',
                'name' => 'Overall Championship',
                'category_ids' => [$cat1->getKey(), $cat2->getKey()],
            ]);

        $response->assertRedirectToRoute('championships.awards.index', $championship);

        $award = ChampionshipAward::first();
        $this->assertEquals(AwardType::Overall, $award->type);
        $this->assertNull($award->category_id);
        $this->assertCount(2, $award->categories);
    }

    public function test_overall_award_requires_categories(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('championships.awards.create', $championship))
            ->post(route('championships.awards.store', $championship), [
                'type' => 'overall',
                'name' => 'Overall',
                'category_ids' => [],
            ]);

        $response->assertSessionHasErrors('category_ids');
    }

    public function test_award_show_page(): void
    {
        $user = User::factory()->racemanager()->create();
        $championship = Championship::factory()->create();
        $category = Category::factory()->create(['championship_id' => $championship->getKey()]);

        $award = ChampionshipAward::factory()->create([
            'championship_id' => $championship->getKey(),
            'category_id' => $category->getKey(),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('awards.show', $award));

        $response->assertSuccessful();
        $response->assertViewIs('award.show');
        $response->assertViewHas('award');
        $response->assertViewHas('ranking');
    }

    public function test_award_edit_form_shown(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();
        $category = Category::factory()->create(['championship_id' => $championship->getKey()]);

        $award = ChampionshipAward::factory()->create([
            'championship_id' => $championship->getKey(),
            'category_id' => $category->getKey(),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('awards.edit', $award));

        $response->assertSuccessful();
        $response->assertViewIs('award.edit');
        $response->assertViewHas('award');
    }

    public function test_award_updated(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();
        $category = Category::factory()->create(['championship_id' => $championship->getKey()]);

        $award = ChampionshipAward::factory()->create([
            'championship_id' => $championship->getKey(),
            'category_id' => $category->getKey(),
            'name' => 'Old Name',
        ]);

        $response = $this
            ->actingAs($user)
            ->from(route('awards.edit', $award))
            ->put(route('awards.update', $award), [
                'type' => 'category',
                'name' => 'Updated Name',
                'category_id' => $category->getKey(),
                'ranking_mode' => 'all',
                'wildcard_filter' => 'exclude',
            ]);

        $response->assertRedirectToRoute('championships.awards.index', $championship);
        $response->assertSessionHas('flash.banner', 'Award updated.');

        $award->refresh();
        $this->assertEquals('Updated Name', $award->name);
        $this->assertEquals(WildcardFilter::ExcludeWildcards, $award->wildcard_filter);
    }

    public function test_award_deleted(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();
        $category = Category::factory()->create(['championship_id' => $championship->getKey()]);

        $award = ChampionshipAward::factory()->create([
            'championship_id' => $championship->getKey(),
            'category_id' => $category->getKey(),
        ]);

        $response = $this
            ->actingAs($user)
            ->delete(route('awards.destroy', $award));

        $response->assertRedirectToRoute('championships.awards.index', $championship);
        $response->assertSessionHas('flash.banner', 'Award deleted.');

        $this->assertDatabaseMissing('championship_awards', ['id' => $award->getKey()]);
    }

    public function test_deleting_award_requires_organizer_level(): void
    {
        $user = User::factory()->racemanager()->create();
        $championship = Championship::factory()->create();
        $category = Category::factory()->create(['championship_id' => $championship->getKey()]);

        $award = ChampionshipAward::factory()->create([
            'championship_id' => $championship->getKey(),
            'category_id' => $category->getKey(),
        ]);

        $response = $this
            ->actingAs($user)
            ->delete(route('awards.destroy', $award));

        $response->assertForbidden();
    }

    public function test_category_award_requires_category_id(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('championships.awards.create', $championship))
            ->post(route('championships.awards.store', $championship), [
                'type' => 'category',
                'name' => 'Test',
                'ranking_mode' => 'all',
            ]);

        $response->assertSessionHasErrors('category_id');
    }

    public function test_best_n_requires_value(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();
        $category = Category::factory()->create(['championship_id' => $championship->getKey()]);

        $response = $this
            ->actingAs($user)
            ->from(route('championships.awards.create', $championship))
            ->post(route('championships.awards.store', $championship), [
                'type' => 'category',
                'name' => 'Test',
                'category_id' => $category->getKey(),
                'ranking_mode' => 'best_n',
            ]);

        $response->assertSessionHasErrors('best_n');
    }
}

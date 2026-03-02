<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Championship;
use App\Models\ChampionshipAward;
use App\Models\Race;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class PublicChampionshipAwardControllerTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_index_is_accessible_without_authentication(): void
    {
        $championship = Championship::factory()->create();

        $response = $this->get(route('public.championships.awards.index', $championship));

        $response->assertSuccessful();
    }

    public function test_index_shows_awards_grouped_by_type(): void
    {
        $championship = Championship::factory()->create();
        $category = Category::factory()->create(['championship_id' => $championship->getKey()]);

        $categoryAward = ChampionshipAward::factory()->categoryAward($category)->create([
            'championship_id' => $championship->getKey(),
            'name' => 'Category Trophy',
        ]);

        $overallAward = ChampionshipAward::factory()->overallAward()->create([
            'championship_id' => $championship->getKey(),
            'name' => 'Overall Trophy',
        ]);
        $overallAward->categories()->sync([$category->getKey()]);

        $response = $this->get(route('public.championships.awards.index', $championship));

        $response->assertSuccessful();
        $response->assertViewIs('public-championship-award.index');
        $response->assertViewHas('groupedAwards');
        $response->assertViewHas('rankingsPerAward');
        $response->assertSee('Category Trophy');
        $response->assertSee('Overall Trophy');

        $groupedAwards = $response->viewData('groupedAwards');
        $this->assertCount(2, $groupedAwards);
    }

    public function test_index_shows_empty_state_when_no_awards(): void
    {
        $championship = Championship::factory()->create();

        $response = $this->get(route('public.championships.awards.index', $championship));

        $response->assertSuccessful();
        $response->assertSee(__('No awards.'));
    }

    public function test_show_is_accessible_without_authentication(): void
    {
        $championship = Championship::factory()->create();
        $category = Category::factory()->create(['championship_id' => $championship->getKey()]);

        $award = ChampionshipAward::factory()->categoryAward($category)->create([
            'championship_id' => $championship->getKey(),
        ]);

        $response = $this->get(route('public.awards.show', $award));

        $response->assertSuccessful();
    }

    public function test_show_displays_award_name_and_ranking(): void
    {
        $championship = Championship::factory()->create();
        $category = Category::factory()->create(['championship_id' => $championship->getKey()]);

        $award = ChampionshipAward::factory()->categoryAward($category)->create([
            'championship_id' => $championship->getKey(),
            'name' => 'Speed Trophy',
        ]);

        $response = $this->get(route('public.awards.show', $award));

        $response->assertSuccessful();
        $response->assertViewIs('public-championship-award.show');
        $response->assertViewHas('award');
        $response->assertViewHas('ranking');
        $response->assertViewHas('races');
        $response->assertSee('Speed Trophy');
    }

    public function test_show_contains_back_link_to_championship_awards(): void
    {
        $championship = Championship::factory()->create();
        $category = Category::factory()->create(['championship_id' => $championship->getKey()]);

        $award = ChampionshipAward::factory()->categoryAward($category)->create([
            'championship_id' => $championship->getKey(),
        ]);

        $response = $this->get(route('public.awards.show', $award));

        $response->assertSee(route('public.championships.awards.index', $championship));
    }

    public function test_index_includes_links_to_individual_award_pages(): void
    {
        $championship = Championship::factory()->create();
        $category = Category::factory()->create(['championship_id' => $championship->getKey()]);

        $award = ChampionshipAward::factory()->categoryAward($category)->create([
            'championship_id' => $championship->getKey(),
        ]);

        $response = $this->get(route('public.championships.awards.index', $championship));

        $response->assertSee(route('public.awards.show', $award));
    }

    public function test_index_shows_race_columns_from_championship(): void
    {
        $championship = Championship::factory()->create();
        $category = Category::factory()->create(['championship_id' => $championship->getKey()]);
        $race = Race::factory()->create([
            'championship_id' => $championship->getKey(),
            'title' => 'Race One',
        ]);

        ChampionshipAward::factory()->categoryAward($category)->create([
            'championship_id' => $championship->getKey(),
        ]);

        $response = $this->get(route('public.championships.awards.index', $championship));

        $response->assertSee('Race One');
    }
}

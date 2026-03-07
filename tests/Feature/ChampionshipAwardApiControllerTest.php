<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AwardType;
use App\Models\Category;
use App\Models\Championship;
use App\Models\ChampionshipAward;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class ChampionshipAwardApiControllerTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_returns_json_list_of_awards(): void
    {
        $championship = Championship::factory()->create();
        $category = Category::factory()->create(['championship_id' => $championship->getKey()]);

        ChampionshipAward::factory()->categoryAward($category)->create([
            'championship_id' => $championship->getKey(),
            'name' => 'Trophy A',
        ]);

        $response = $this->getJson(route('api.championship.awards', $championship));

        $response->assertSuccessful();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Trophy A');
        $response->assertJsonPath('data.0.type', AwardType::Category->value);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['ulid', 'name', 'type', 'url'],
            ],
        ]);
    }

    public function test_url_points_to_public_award_show_page(): void
    {
        $championship = Championship::factory()->create();
        $category = Category::factory()->create(['championship_id' => $championship->getKey()]);

        $award = ChampionshipAward::factory()->categoryAward($category)->create([
            'championship_id' => $championship->getKey(),
        ]);

        $response = $this->getJson(route('api.championship.awards', $championship));

        $response->assertJsonPath('data.0.url', route('public.awards.show', $award));
    }

    public function test_returns_empty_data_when_no_awards(): void
    {
        $championship = Championship::factory()->create();

        $response = $this->getJson(route('api.championship.awards', $championship));

        $response->assertSuccessful();
        $response->assertJsonCount(0, 'data');
    }

    public function test_returns_awards_ordered_by_name(): void
    {
        $championship = Championship::factory()->create();
        $category = Category::factory()->create(['championship_id' => $championship->getKey()]);

        ChampionshipAward::factory()->categoryAward($category)->create([
            'championship_id' => $championship->getKey(),
            'name' => 'Zebra Trophy',
        ]);

        ChampionshipAward::factory()->overallAward()->create([
            'championship_id' => $championship->getKey(),
            'name' => 'Alpha Trophy',
        ]);

        $response = $this->getJson(route('api.championship.awards', $championship));

        $response->assertJsonPath('data.0.name', 'Alpha Trophy');
        $response->assertJsonPath('data.1.name', 'Zebra Trophy');
    }

    public function test_does_not_include_awards_from_other_championships(): void
    {
        $championship = Championship::factory()->create();
        $otherChampionship = Championship::factory()->create();
        $category = Category::factory()->create(['championship_id' => $otherChampionship->getKey()]);

        ChampionshipAward::factory()->categoryAward($category)->create([
            'championship_id' => $otherChampionship->getKey(),
        ]);

        $response = $this->getJson(route('api.championship.awards', $championship));

        $response->assertJsonCount(0, 'data');
    }

    public function test_is_accessible_without_authentication(): void
    {
        $championship = Championship::factory()->create();

        $response = $this->getJson(route('api.championship.awards', $championship));

        $response->assertSuccessful();
    }
}

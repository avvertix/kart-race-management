<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Championship;
use App\Models\Participant;
use App\Models\Race;
use Carbon\Carbon;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class ListUnderageParticipantsCommandTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_lists_participants_under_18_in_category(): void
    {
        $championship = Championship::factory()->create();
        $race = Race::factory()->recycle($championship)->create();
        $category = Category::factory()->recycle($championship)->create(['name' => 'Junior']);

        Participant::factory()->recycle($race)->category($category)->driver([
            'birth_date' => Carbon::now()->subYears(15)->toDateString(),
        ])->create();

        Participant::factory()->recycle($race)->category($category)->driver([
            'birth_date' => Carbon::now()->subYears(20)->toDateString(),
        ])->create();

        $this->artisan('race:list-underage', [
            'race' => $race->uuid,
            'category' => 'Junior',
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('Junior')
            ->expectsOutputToContain('15');
    }

    public function test_matches_category_by_short_name(): void
    {
        $championship = Championship::factory()->create();
        $race = Race::factory()->recycle($championship)->create();
        $category = Category::factory()->recycle($championship)->create([
            'name' => 'Junior',
            'short_name' => 'JUN',
        ]);

        Participant::factory()->recycle($race)->category($category)->driver([
            'birth_date' => Carbon::now()->subYears(16)->toDateString(),
        ])->create();

        $this->artisan('race:list-underage', [
            'race' => $race->uuid,
            'category' => 'JUN',
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('Junior');
    }

    public function test_excludes_participants_aged_18_and_over(): void
    {
        $championship = Championship::factory()->create();
        $race = Race::factory()->recycle($championship)->create();
        $category = Category::factory()->recycle($championship)->create(['name' => 'Senior']);

        Participant::factory()->recycle($race)->category($category)->driver([
            'birth_date' => Carbon::now()->subYears(25)->toDateString(),
        ])->create();

        $this->artisan('race:list-underage', [
            'race' => $race->uuid,
            'category' => 'Senior',
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('No participants under 18');
    }

    public function test_fails_with_unknown_race(): void
    {
        $this->artisan('race:list-underage', [
            'race' => 'non-existent-uuid',
            'category' => 'Junior',
        ])->assertFailed();
    }

    public function test_fails_with_unknown_category(): void
    {
        $race = Race::factory()->create();

        $this->artisan('race:list-underage', [
            'race' => $race->uuid,
            'category' => 'NonExistent',
        ])->assertFailed();
    }
}

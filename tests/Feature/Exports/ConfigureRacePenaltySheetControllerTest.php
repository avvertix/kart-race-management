<?php

declare(strict_types=1);

namespace Tests\Feature\Exports;

use App\Models\Category;
use App\Models\Participant;
use App\Models\Race;
use App\Models\User;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class ConfigureRacePenaltySheetControllerTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_configure_requires_authentication(): void
    {
        $race = Race::factory()->create();

        $response = $this->get(route('races.penalty-sheet.configure', $race));

        $response->assertRedirect(route('login'));
    }

    public function test_configure_forbidden_for_tireagent(): void
    {
        $user = User::factory()->tireagent()->create();
        $race = Race::factory()->create();

        $response = $this->actingAs($user)->get(route('races.penalty-sheet.configure', $race));

        $response->assertForbidden();
    }

    public function test_configure_forbidden_for_timekeeper(): void
    {
        $user = User::factory()->timekeeper()->create();
        $race = Race::factory()->create();

        $response = $this->actingAs($user)->get(route('races.penalty-sheet.configure', $race));

        $response->assertForbidden();
    }

    public function test_configure_forbidden_for_racemanager(): void
    {
        $user = User::factory()->racemanager()->create();
        $race = Race::factory()->create();

        $response = $this->actingAs($user)->get(route('races.penalty-sheet.configure', $race));

        $response->assertForbidden();
    }

    public function test_configure_page_shows_categories_with_confirmed_participants(): void
    {
        $user = User::factory()->organizer()->create();
        $race = Race::factory()->create();

        $categoryA = Category::factory()->recycle($race->championship)->create(['name' => 'Mini Junior']);
        $categoryB = Category::factory()->recycle($race->championship)->create(['name' => 'KZ2']);

        Participant::factory()->recycle($race->championship)->confirmed()->create([
            'race_id' => $race->getKey(),
            'category_id' => $categoryA->getKey(),
        ]);
        Participant::factory()->recycle($race->championship)->confirmed()->create([
            'race_id' => $race->getKey(),
            'category_id' => $categoryB->getKey(),
        ]);

        $response = $this->actingAs($user)->get(route('races.penalty-sheet.configure', $race));

        $response->assertOk();
        $response->assertSee('Mini Junior');
        $response->assertSee('KZ2');
    }

    public function test_configure_page_excludes_categories_without_confirmed_participants(): void
    {
        $user = User::factory()->organizer()->create();
        $race = Race::factory()->create();

        $categoryA = Category::factory()->recycle($race->championship)->create(['name' => 'Mini Junior']);
        $categoryB = Category::factory()->recycle($race->championship)->create(['name' => 'KZ2']);

        Participant::factory()->recycle($race->championship)->confirmed()->create([
            'race_id' => $race->getKey(),
            'category_id' => $categoryA->getKey(),
        ]);
        Participant::factory()->recycle($race->championship)->create([
            'race_id' => $race->getKey(),
            'category_id' => $categoryB->getKey(),
        ]);

        $response = $this->actingAs($user)->get(route('races.penalty-sheet.configure', $race));

        $response->assertOk();
        $response->assertSee('Mini Junior');
        $response->assertDontSee('KZ2');
    }

    public function test_configure_page_shows_empty_message_when_no_confirmed_participants(): void
    {
        $user = User::factory()->organizer()->create();
        $race = Race::factory()->create();

        $response = $this->actingAs($user)->get(route('races.penalty-sheet.configure', $race));

        $response->assertOk();
        $response->assertSee(__('No confirmed participants found for this race.'));
    }
}

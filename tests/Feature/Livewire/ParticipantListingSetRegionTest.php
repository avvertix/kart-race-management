<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Enums\ItalianRegion;
use App\Livewire\ParticipantListing;
use App\Models\Category;
use App\Models\Participant;
use App\Models\Race;
use App\Models\RaceType;
use App\Models\User;
use Livewire\Livewire;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class ParticipantListingSetRegionTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_set_region_stores_region_on_participant(): void
    {
        $user = User::factory()->racemanager()->create();
        $race = Race::factory()->create(['type' => RaceType::NATIONAL->value]);
        $category = Category::factory()->recycle($race->championship)->create();
        $participant = Participant::factory()->category($category)->create([
            'race_id' => $race->getKey(),
            'championship_id' => $race->championship_id,
            'region' => null,
        ]);

        Livewire::actingAs($user)
            ->test(ParticipantListing::class, ['race' => $race])
            ->call('setRegion', $participant->getKey(), ItalianRegion::LOMBARDIA->value);

        $this->assertSame(ItalianRegion::LOMBARDIA, $participant->fresh()->region);
    }

    public function test_set_region_clears_region_when_empty_value_given(): void
    {
        $user = User::factory()->racemanager()->create();
        $race = Race::factory()->create(['type' => RaceType::NATIONAL->value]);
        $category = Category::factory()->recycle($race->championship)->create();
        $participant = Participant::factory()->category($category)->create([
            'race_id' => $race->getKey(),
            'championship_id' => $race->championship_id,
            'region' => ItalianRegion::LOMBARDIA->value,
        ]);

        Livewire::actingAs($user)
            ->test(ParticipantListing::class, ['race' => $race])
            ->call('setRegion', $participant->getKey(), '');

        $this->assertNull($participant->fresh()->region);
    }

    public function test_set_region_updates_out_of_zone_when_race_has_zone_configured(): void
    {
        $user = User::factory()->racemanager()->create();
        $race = Race::factory()->create([
            'type' => RaceType::NATIONAL->value,
            'zone_regions' => [ItalianRegion::LOMBARDIA->value],
        ]);
        $category = Category::factory()->recycle($race->championship)->create();
        $participant = Participant::factory()->category($category)->create([
            'race_id' => $race->getKey(),
            'championship_id' => $race->championship_id,
            'region' => null,
        ]);

        Livewire::actingAs($user)
            ->test(ParticipantListing::class, ['race' => $race])
            ->call('setRegion', $participant->getKey(), ItalianRegion::LAZIO->value);

        $fresh = $participant->fresh();
        $this->assertSame(ItalianRegion::LAZIO, $fresh->region);
        $this->assertTrue($fresh->properties['out_of_zone']);
    }

    public function test_set_region_marks_within_zone_when_region_is_in_zone(): void
    {
        $user = User::factory()->racemanager()->create();
        $race = Race::factory()->create([
            'type' => RaceType::NATIONAL->value,
            'zone_regions' => [ItalianRegion::LOMBARDIA->value, ItalianRegion::PIEMONTE->value],
        ]);
        $category = Category::factory()->recycle($race->championship)->create();
        $participant = Participant::factory()->category($category)->create([
            'race_id' => $race->getKey(),
            'championship_id' => $race->championship_id,
            'region' => null,
        ]);

        Livewire::actingAs($user)
            ->test(ParticipantListing::class, ['race' => $race])
            ->call('setRegion', $participant->getKey(), ItalianRegion::PIEMONTE->value);

        $fresh = $participant->fresh();
        $this->assertSame(ItalianRegion::PIEMONTE, $fresh->region);
        $this->assertFalse($fresh->properties['out_of_zone']);
    }

    public function test_set_region_requires_authorization(): void
    {
        $user = User::factory()->create();
        $race = Race::factory()->create(['type' => RaceType::NATIONAL->value]);
        $category = Category::factory()->recycle($race->championship)->create();
        $participant = Participant::factory()->category($category)->create([
            'race_id' => $race->getKey(),
            'championship_id' => $race->championship_id,
        ]);

        Livewire::actingAs($user)
            ->test(ParticipantListing::class, ['race' => $race])
            ->call('setRegion', $participant->getKey(), ItalianRegion::LOMBARDIA->value)
            ->assertForbidden();
    }
}

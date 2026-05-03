<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire;

use App\Livewire\LinkPastRaces;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class LinkPastRacesTest extends TestCase
{
    use RefreshDatabase;

    public function test_component_renders(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(LinkPastRaces::class)
            ->assertOk();
    }

    // --- Search ---

    public function test_search_by_licence_number_finds_participant(): void
    {
        $user = User::factory()->create();
        $licenceNumber = 'LICENCE-SEARCH-99';
        $participant = Participant::factory()->create([
            'driver_licence' => hash('sha512', $licenceNumber),
        ]);

        $component = Livewire::actingAs($user)
            ->test(LinkPastRaces::class)
            ->set('search', $licenceNumber)
            ->call('performSearch');

        $this->assertTrue($component->viewData('participants')->contains('uuid', $participant->uuid));
    }

    public function test_search_requires_minimum_length(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(LinkPastRaces::class)
            ->set('search', 'ab')
            ->call('performSearch')
            ->assertHasErrors(['search']);
    }

    public function test_clear_search_resets_state(): void
    {
        $user = User::factory()->create();
        $licenceNumber = 'CLEAR-TEST-001';
        Participant::factory()->create(['driver_licence' => hash('sha512', $licenceNumber)]);

        Livewire::actingAs($user)
            ->test(LinkPastRaces::class)
            ->set('search', $licenceNumber)
            ->call('performSearch')
            ->assertSet('verifiedSearch', $licenceNumber)
            ->call('clearSearch')
            ->assertSet('verifiedSearch', null)
            ->assertSet('search', '');
    }

    public function test_draft_participants_are_excluded(): void
    {
        $user = User::factory()->create();
        $draft = Participant::factory()->create([
            'driver_licence' => hash('sha512', 'DRAFT-LICENCE'),
            'status' => 'draft',
        ]);

        $component = Livewire::actingAs($user)
            ->test(LinkPastRaces::class)
            ->set('search', 'DRAFT-LICENCE')
            ->call('performSearch');

        $this->assertFalse($component->viewData('participants')->contains('uuid', $draft->uuid));
    }

    // --- Linking ---

    public function test_link_sets_claimed_by_on_unclaimed_participant(): void
    {
        $user = User::factory()->create();
        $licenceNumber = 'LINK-LICENCE-01';
        $participant = Participant::factory()->create([
            'driver_licence' => hash('sha512', $licenceNumber),
            'added_by' => null,
        ]);

        Livewire::actingAs($user)
            ->test(LinkPastRaces::class)
            ->set('search', $licenceNumber)
            ->call('performSearch')
            ->call('link', $participant->uuid)
            ->assertSet('linkedUuids', [$participant->uuid]);

        $this->assertEquals($user->id, $participant->fresh()->claimed_by);
        $this->assertNull($participant->fresh()->added_by);
    }

    public function test_link_does_not_override_existing_added_by(): void
    {
        $owner = User::factory()->create();
        $user = User::factory()->create();
        $licenceNumber = 'LINK-NO-OVERRIDE';
        $participant = Participant::factory()->create([
            'driver_licence' => hash('sha512', $licenceNumber),
            'added_by' => $owner->id,
        ]);

        Livewire::actingAs($user)
            ->test(LinkPastRaces::class)
            ->set('search', $licenceNumber)
            ->call('performSearch')
            ->call('link', $participant->uuid);

        $this->assertEquals($owner->id, $participant->fresh()->added_by);
    }

    public function test_link_without_verified_search_is_rejected_for_unowned_participant(): void
    {
        $user = User::factory()->create();
        $participant = Participant::factory()->create(['added_by' => null]);

        Livewire::actingAs($user)
            ->test(LinkPastRaces::class)
            ->call('link', $participant->uuid)
            ->assertHasErrors(['action']);

        $this->assertNull($participant->fresh()->claimed_by);
    }

    // --- Link all ---

    public function test_link_all_links_all_search_results(): void
    {
        $user = User::factory()->create();
        $licenceNumber = 'LINK-ALL-TEST-01';

        $participants = Participant::factory()->count(3)->create([
            'driver_licence' => hash('sha512', $licenceNumber),
            'added_by' => null,
        ]);

        $component = Livewire::actingAs($user)
            ->test(LinkPastRaces::class)
            ->set('search', $licenceNumber)
            ->call('performSearch')
            ->call('linkAll');

        foreach ($participants as $participant) {
            $this->assertEquals($user->id, $participant->fresh()->claimed_by);
            $this->assertNull($participant->fresh()->added_by);
            $this->assertContains((string) $participant->uuid, $component->get('linkedUuids'));
        }
    }

    public function test_link_all_does_not_override_existing_added_by(): void
    {
        $owner = User::factory()->create();
        $user = User::factory()->create();
        $licenceNumber = 'LINK-ALL-NO-OVERRIDE';

        $participant = Participant::factory()->create([
            'driver_licence' => hash('sha512', $licenceNumber),
            'added_by' => $owner->id,
        ]);

        Livewire::actingAs($user)
            ->test(LinkPastRaces::class)
            ->set('search', $licenceNumber)
            ->call('performSearch')
            ->call('linkAll');

        $this->assertEquals($owner->id, $participant->fresh()->added_by);
    }

    public function test_link_all_without_verified_search_is_rejected(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(LinkPastRaces::class)
            ->call('linkAll')
            ->assertHasErrors(['action']);
    }

    // --- Misc ---

    public function test_participants_from_previous_year_championships_are_excluded(): void
    {
        $user = User::factory()->create();
        $licenceNumber = 'OLD-LICENCE-2024';

        $oldChampionship = \App\Models\Championship::factory()->create([
            'start_at' => today()->subYear()->startOfYear(),
        ]);
        $race = \App\Models\Race::factory()->create(['championship_id' => $oldChampionship->id]);
        $participant = Participant::factory()->create([
            'driver_licence' => hash('sha512', $licenceNumber),
            'championship_id' => $oldChampionship->id,
            'race_id' => $race->id,
        ]);

        $component = Livewire::actingAs($user)
            ->test(LinkPastRaces::class)
            ->set('search', $licenceNumber)
            ->call('performSearch');

        $this->assertFalse($component->viewData('participants')->contains('uuid', $participant->uuid));
    }

}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Participant;
use App\Models\PaymentChannelType;
use App\Models\Race;
use App\Models\User;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class RacePaymentsControllerTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_authentication_required(): void
    {
        $race = Race::factory()->create();

        $response = $this->get(route('races.payments', $race));

        $response->assertRedirectToRoute('login');
    }

    public function test_timekeeper_cannot_access(): void
    {
        $user = User::factory()->timekeeper()->create();
        $race = Race::factory()->create();

        $response = $this->actingAs($user)->get(route('races.payments', $race));

        $response->assertForbidden();
    }

    public function test_participants_are_listed(): void
    {
        $user = User::factory()->organizer()->create();
        $race = Race::factory()->create();

        $participant = $this->createParticipant($race);

        $otherRace = Race::factory()->create([
            'championship_id' => $race->championship->getKey(),
        ]);

        $this->createParticipant($otherRace);

        $response = $this->actingAs($user)->get(route('races.payments', $race));

        $response->assertSuccessful();
        $response->assertViewHas('race', $race);
        $response->assertViewHas('championship', $race->championship);

        $participants = $response->viewData('participants');
        $this->assertCount(1, $participants);
        $this->assertTrue($participants->first()->is($participant));
    }

    public function test_search_filters_by_bib(): void
    {
        $user = User::factory()->organizer()->create();
        $race = Race::factory()->create();

        $participant = $this->createParticipant($race, ['bib' => 42]);
        $this->createParticipant($race, ['bib' => 99]);

        $response = $this->actingAs($user)->get(route('races.payments', ['race' => $race, 's' => '42']));

        $response->assertSuccessful();

        $participants = $response->viewData('participants');
        $this->assertCount(1, $participants);
        $this->assertTrue($participants->first()->is($participant));
    }

    public function test_search_filters_by_first_name(): void
    {
        $user = User::factory()->organizer()->create();
        $race = Race::factory()->create();

        $participant = $this->createParticipant($race, ['first_name' => 'Mario']);
        $this->createParticipant($race, ['first_name' => 'Luigi']);

        $response = $this->actingAs($user)->get(route('races.payments', ['race' => $race, 's' => 'Mario']));

        $response->assertSuccessful();

        $participants = $response->viewData('participants');
        $this->assertCount(1, $participants);
        $this->assertTrue($participants->first()->is($participant));
    }

    public function test_filter_by_payment_channel(): void
    {
        $user = User::factory()->organizer()->create();
        $race = Race::factory()->create();

        $cashParticipant = $this->createParticipant($race, ['payment_channel' => PaymentChannelType::CASH]);
        $this->createParticipant($race, ['payment_channel' => PaymentChannelType::BANK_TRANSFER]);

        $response = $this->actingAs($user)->get(route('races.payments', [
            'race' => $race,
            'channel' => PaymentChannelType::CASH->value,
        ]));

        $response->assertSuccessful();

        $participants = $response->viewData('participants');
        $this->assertCount(1, $participants);
        $this->assertTrue($participants->first()->is($cashParticipant));
    }

    public function test_filter_by_no_payment_channel(): void
    {
        $user = User::factory()->organizer()->create();
        $race = Race::factory()->create();

        $noChannelParticipant = $this->createParticipant($race, ['payment_channel' => null]);
        $this->createParticipant($race, ['payment_channel' => PaymentChannelType::CASH]);

        $response = $this->actingAs($user)->get(route('races.payments', [
            'race' => $race,
            'channel' => 'none',
        ]));

        $response->assertSuccessful();

        $participants = $response->viewData('participants');
        $this->assertCount(1, $participants);
        $this->assertTrue($participants->first()->is($noChannelParticipant));
    }

    public function test_filter_by_confirmed_payment(): void
    {
        $user = User::factory()->organizer()->create();
        $race = Race::factory()->create();

        $confirmed = $this->createParticipant($race, ['payment_confirmed_at' => now()]);
        $this->createParticipant($race, ['payment_confirmed_at' => null]);

        $response = $this->actingAs($user)->get(route('races.payments', [
            'race' => $race,
            'confirmed' => 'confirmed',
        ]));

        $response->assertSuccessful();

        $participants = $response->viewData('participants');
        $this->assertCount(1, $participants);
        $this->assertTrue($participants->first()->is($confirmed));
    }

    public function test_filter_by_unconfirmed_payment(): void
    {
        $user = User::factory()->organizer()->create();
        $race = Race::factory()->create();

        $unconfirmed = $this->createParticipant($race, ['payment_confirmed_at' => null]);
        $this->createParticipant($race, ['payment_confirmed_at' => now()]);

        $response = $this->actingAs($user)->get(route('races.payments', [
            'race' => $race,
            'confirmed' => 'unconfirmed',
        ]));

        $response->assertSuccessful();

        $participants = $response->viewData('participants');
        $this->assertCount(1, $participants);
        $this->assertTrue($participants->first()->is($unconfirmed));
    }

    public function test_summary_counts_all_participants_regardless_of_filters(): void
    {
        $user = User::factory()->organizer()->create();
        $race = Race::factory()->create();

        $this->createParticipant($race, ['payment_channel' => PaymentChannelType::CASH]);
        $this->createParticipant($race, ['payment_channel' => PaymentChannelType::BANK_TRANSFER]);

        // Filter to show only cash, but summary should still count all 2 participants
        $response = $this->actingAs($user)->get(route('races.payments', [
            'race' => $race,
            'channel' => PaymentChannelType::CASH->value,
        ]));

        $response->assertSuccessful();

        $summary = $response->viewData('summary');
        $this->assertEquals(2, $summary->sum('count'));

        $participants = $response->viewData('participants');
        $this->assertCount(1, $participants);
    }

    private function createParticipant(Race $race, array $attributes = []): Participant
    {
        $category = Category::factory()->create([
            'championship_id' => $race->championship->getKey(),
        ]);

        return Participant::factory()->category($category)->create(array_merge([
            'race_id' => $race->getKey(),
            'championship_id' => $race->championship->getKey(),
        ], $attributes));
    }
}

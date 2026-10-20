<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\CalculateAwardRanking;
use App\Models\Category;
use App\Models\Championship;
use App\Models\ChampionshipAward;
use App\Models\Participant;
use App\Models\ParticipantResult;
use App\Models\Race;
use App\Models\RunResult;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ChampionshipDuplicateDriverControllerTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_listing_duplicate_drivers_requires_login()
    {
        $championship = Championship::factory()->create();

        $response = $this->get(route('championships.duplicate-drivers.index', $championship));

        $response->assertRedirectToRoute('login');
    }

    public function test_organizer_cannot_list_duplicate_drivers()
    {
        $user = User::factory()->organizer()->create();

        $championship = Championship::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('championships.duplicate-drivers.index', $championship));

        $response->assertForbidden();
    }

    public function test_organizer_cannot_merge_duplicate_drivers()
    {
        $user = User::factory()->organizer()->create();

        [$championship, $first, $second] = $this->createDriverRegisteredWithDifferentLicences('D-25-000123-2026', '25-000123-2026');

        $response = $this
            ->actingAs($user)
            ->post(route('championships.duplicate-drivers.store', $championship), [
                'canonical' => (string) $first->uuid,
                'duplicates' => [(string) $second->uuid],
            ]);

        $response->assertForbidden();

        $this->assertSame(hash('sha512', '25-000123-2026'), $second->fresh()->driver_licence);
    }

    public function test_participations_with_same_name_and_different_licence_are_listed()
    {
        $user = User::factory()->admin()->create();

        [$championship, $first, $second] = $this->createDriverRegisteredWithDifferentLicences('D-25-000123-2026', '25-000123-2026');

        $response = $this
            ->actingAs($user)
            ->get(route('championships.duplicate-drivers.index', $championship));

        $response->assertSuccessful();

        $groups = $response->viewData('groups');

        $this->assertCount(1, $groups);
        $this->assertEqualsCanonicalizing([$first->uuid, $second->uuid], $groups->first()['participants']->pluck('uuid')->all());
        $this->assertTrue($groups->first()['same_name']);
        $this->assertTrue($groups->first()['same_birth_date']);
        $this->assertFalse($groups->first()['same_licence']);

        $response->assertSee('D-25-000123-2026');
        $response->assertSee('25-000123-2026');
    }

    public function test_participations_with_similar_licence_and_different_name_are_listed()
    {
        $user = User::factory()->admin()->create();

        $championship = Championship::factory()->has(Race::factory()->count(2))->create();

        $first = $this->registerDriver($championship->races->first(), 'Ivan', 'Testov', 'КД.99.0001.26');
        $second = $this->registerDriver($championship->races->last(), 'Yvan', 'Testov', 'KD.99.0001.26');

        $response = $this
            ->actingAs($user)
            ->get(route('championships.duplicate-drivers.index', $championship));

        $response->assertSuccessful();

        $groups = $response->viewData('groups');

        $this->assertCount(1, $groups);
        $this->assertEqualsCanonicalizing([$first->uuid, $second->uuid], $groups->first()['participants']->pluck('uuid')->all());
        $this->assertFalse($groups->first()['same_name']);
        $this->assertTrue($groups->first()['same_licence']);
    }

    public function test_participations_with_same_licence_and_outdated_racer_hash_are_listed()
    {
        $user = User::factory()->admin()->create();

        $championship = Championship::factory()->has(Race::factory()->count(2))->create();

        $first = $this->registerDriver($championship->races->first(), 'Marco', 'Bianchi', '100200');
        $first->update(['racer_hash' => 'deadbeef']);

        $this->registerDriver($championship->races->last(), 'Marco', 'Bianchi', '100200');

        $response = $this
            ->actingAs($user)
            ->get(route('championships.duplicate-drivers.index', $championship));

        $response->assertSuccessful();

        $this->assertCount(1, $response->viewData('groups'));

        $response->assertSee('Not matching licence');
    }

    public function test_driver_identified_by_the_same_racer_hash_is_not_listed()
    {
        $user = User::factory()->admin()->create();

        $championship = Championship::factory()->has(Race::factory()->count(2))->create();

        $this->registerDriver($championship->races->first(), 'Marco', 'Bianchi', '100200');
        $this->registerDriver($championship->races->last(), 'Marco', 'Bianchi', '100200');

        $response = $this
            ->actingAs($user)
            ->get(route('championships.duplicate-drivers.index', $championship));

        $response->assertSuccessful();

        $this->assertCount(0, $response->viewData('groups'));

        $response->assertSee('No duplicate drivers found.');
    }

    public function test_participations_in_other_championships_are_not_listed()
    {
        $user = User::factory()->admin()->create();

        [$championship] = $this->createDriverRegisteredWithDifferentLicences('D-25-000123-2026', '25-000123-2026');

        $otherChampionship = Championship::factory()->has(Race::factory())->create();

        $this->registerDriver($otherChampionship->races->first(), 'Giulia', 'Verdi', 'D25-000123-26');

        $response = $this
            ->actingAs($user)
            ->get(route('championships.duplicate-drivers.index', $championship));

        $response->assertSuccessful();

        $this->assertCount(2, $response->viewData('groups')->first()['participants']);
    }

    public function test_duplicates_receive_licence_of_canonical_participation()
    {
        $user = User::factory()->admin()->create();

        [$championship, $first, $second] = $this->createDriverRegisteredWithDifferentLicences('D-25-000123-2026', '25-000123-2026');

        $response = $this
            ->actingAs($user)
            ->post(route('championships.duplicate-drivers.store', $championship), [
                'canonical' => (string) $first->uuid,
                'duplicates' => [(string) $second->uuid],
            ]);

        $response->assertRedirectToRoute('championships.duplicate-drivers.index', $championship);
        $response->assertSessionHas('flash.banner', 'Participations of Giulia Verdi merged.');

        $expectedHash = hash('sha512', 'D-25-000123-2026');

        $second->refresh();

        $this->assertSame($expectedHash, $second->driver_licence);
        $this->assertSame(mb_substr($expectedHash, 0, 8), $second->racer_hash);
        $this->assertSame('D-25-000123-2026', $second->driver['licence_number']);
        $this->assertSame('Verdi', $second->driver['last_name']);

        $activity = Activity::forSubject($second)->forEvent('merged')->sole();

        $this->assertTrue($activity->causer->is($user));
        $this->assertSame((string) $first->uuid, $activity->getProperty('merged_into'));
        $this->assertSame('25-000123-2026', Crypt::decryptString($activity->getProperty('previous_licence_number')));
        $this->assertFalse(Activity::forSubject($second)->forEvent('updated')->exists());
    }

    public function test_participant_activity_shows_merged_licence()
    {
        $user = User::factory()->admin()->create();

        $championship = Championship::factory()->has(Race::factory()->count(2))->create();

        $first = $this->registerDriver($championship->races->first(), 'Giulia', 'Verdi', 'D-25-000123-2026');

        // The participant page requires a category to show the price
        $second = Participant::factory()
            ->for($championship->races->last())
            ->for($championship)
            ->category()
            ->driver(['first_name' => 'Giulia', 'last_name' => 'Verdi', 'licence_number' => '25-000123-2026'])
            ->create();

        $previousRacerHash = $second->racer_hash;

        $this
            ->actingAs($user)
            ->post(route('championships.duplicate-drivers.store', $championship), [
                'canonical' => (string) $first->uuid,
                'duplicates' => [(string) $second->uuid],
            ]);

        $response = $this
            ->actingAs($user)
            ->get(route('participants.show', $second));

        $response->assertOk();
        $response->assertSeeInOrder([
            'driver merged',
            "Licence taken from {$first->race->title} (bib {$first->bib})",
            'Licence number',
            '25-000123-2026',
            'D-25-000123-2026',
            'Racer hash',
            $previousRacerHash,
            mb_substr(hash('sha512', 'D-25-000123-2026'), 0, 8),
        ]);
    }

    public function test_merge_realigns_outdated_racer_hash_of_canonical_participation()
    {
        $user = User::factory()->admin()->create();

        $championship = Championship::factory()->has(Race::factory()->count(2))->create();

        $first = $this->registerDriver($championship->races->first(), 'Marco', 'Bianchi', '100200');
        $first->update(['racer_hash' => 'deadbeef']);

        $second = $this->registerDriver($championship->races->last(), 'Marco', 'Bianchi', '100200');

        $response = $this
            ->actingAs($user)
            ->post(route('championships.duplicate-drivers.store', $championship), [
                'canonical' => (string) $first->uuid,
                'duplicates' => [(string) $second->uuid],
            ]);

        $response->assertRedirectToRoute('championships.duplicate-drivers.index', $championship);

        $this->assertSame(mb_substr(hash('sha512', '100200'), 0, 8), $first->fresh()->racer_hash);
    }

    public function test_award_ranks_merged_participations_as_one_driver()
    {
        $user = User::factory()->admin()->create();

        [$championship, $first, $second] = $this->createDriverRegisteredWithDifferentLicences('D-25-000123-2026', '25-000123-2026');

        $category = Category::factory()->recycle($championship)->create();

        $award = ChampionshipAward::factory()->recycle($championship)->create(['category_id' => $category->getKey()]);

        foreach ([$first, $second] as $participation) {
            $participation->update(['category_id' => $category->getKey()]);

            ParticipantResult::factory()
                ->forParticipant($participation)
                ->for(RunResult::factory()->published()->for($participation->race))
                ->create(['points' => 10]);
        }

        $response = $this
            ->actingAs($user)
            ->post(route('championships.duplicate-drivers.store', $championship), [
                'canonical' => (string) $first->uuid,
                'duplicates' => [(string) $second->uuid],
            ]);

        $response->assertRedirectToRoute('championships.duplicate-drivers.index', $championship);

        $ranking = app(CalculateAwardRanking::class)($award);

        $this->assertCount(1, $ranking);
        $this->assertSame(20.0, $ranking->first()['total_points']);

        $expectedRacerHash = mb_substr(hash('sha512', 'D-25-000123-2026'), 0, 8);

        $this->assertSame([$expectedRacerHash], $second->results()->pluck('racer_hash')->unique()->all());
    }

    public function test_merge_links_results_still_using_previous_racer_hash()
    {
        $user = User::factory()->admin()->create();

        [$championship, $first, $second] = $this->createDriverRegisteredWithDifferentLicences('D-25-000123-2026', '25-000123-2026');

        $runResult = RunResult::factory()->for($second->race)->create();

        $unlinkedResult = ParticipantResult::factory()->for($runResult)->create(['racer_hash' => $second->racer_hash]);

        $resultOfOtherDriver = ParticipantResult::factory()->for($runResult)->create(['racer_hash' => 'a1b2c3d4']);

        $response = $this
            ->actingAs($user)
            ->post(route('championships.duplicate-drivers.store', $championship), [
                'canonical' => (string) $first->uuid,
                'duplicates' => [(string) $second->uuid],
            ]);

        $response->assertRedirectToRoute('championships.duplicate-drivers.index', $championship);

        $unlinkedResult->refresh();

        $this->assertSame($second->getKey(), $unlinkedResult->participant_id);
        $this->assertSame($second->fresh()->racer_hash, $unlinkedResult->racer_hash);
        $this->assertNull($resultOfOtherDriver->fresh()->participant_id);
    }

    public function test_participations_of_the_same_race_cannot_be_merged()
    {
        $user = User::factory()->admin()->create();

        $championship = Championship::factory()->has(Race::factory())->create();

        $first = $this->registerDriver($championship->races->first(), 'Luca', 'Neri', '100300');
        $second = $this->registerDriver($championship->races->first(), 'Neri', 'Luca', '123456789');

        $response = $this
            ->actingAs($user)
            ->from(route('championships.duplicate-drivers.index', $championship))
            ->post(route('championships.duplicate-drivers.store', $championship), [
                'canonical' => (string) $first->uuid,
                'duplicates' => [(string) $second->uuid],
            ]);

        $response->assertSessionHasErrors(['duplicates' => 'Participations of the same race cannot be merged as they belong to different drivers.']);

        $this->assertSame(hash('sha512', '123456789'), $second->fresh()->driver_licence);
    }

    public function test_participations_of_other_championships_cannot_be_merged()
    {
        $user = User::factory()->admin()->create();

        [$championship, $first] = $this->createDriverRegisteredWithDifferentLicences('D-25-000123-2026', '25-000123-2026');

        $otherChampionship = Championship::factory()->has(Race::factory())->create();

        $otherParticipation = $this->registerDriver($otherChampionship->races->first(), 'Giulia', 'Verdi', 'D25-000123-26');

        $response = $this
            ->actingAs($user)
            ->from(route('championships.duplicate-drivers.index', $championship))
            ->post(route('championships.duplicate-drivers.store', $championship), [
                'canonical' => (string) $first->uuid,
                'duplicates' => [(string) $otherParticipation->uuid],
            ]);

        $response->assertSessionHasErrors('duplicates.0');

        $this->assertSame(hash('sha512', 'D25-000123-26'), $otherParticipation->fresh()->driver_licence);
    }

    public function test_merge_requires_at_least_one_other_participation()
    {
        $user = User::factory()->admin()->create();

        [$championship, $first] = $this->createDriverRegisteredWithDifferentLicences('D-25-000123-2026', '25-000123-2026');

        $response = $this
            ->actingAs($user)
            ->from(route('championships.duplicate-drivers.index', $championship))
            ->post(route('championships.duplicate-drivers.store', $championship), [
                'canonical' => (string) $first->uuid,
                'duplicates' => [(string) $first->uuid],
            ]);

        $response->assertSessionHasErrors(['duplicates' => 'Select at least one participation to merge.']);
    }

    /**
     * @return array{0: Championship, 1: Participant, 2: Participant}
     */
    private function createDriverRegisteredWithDifferentLicences(string $firstLicence, string $secondLicence): array
    {
        $championship = Championship::factory()->has(Race::factory()->count(2))->create();

        return [
            $championship,
            $this->registerDriver($championship->races->first(), 'Giulia', 'Verdi', $firstLicence),
            $this->registerDriver($championship->races->last(), 'Giulia', 'Verdi', $secondLicence),
        ];
    }

    private function registerDriver(Race $race, string $firstName, string $lastName, string $licence): Participant
    {
        return Participant::factory()
            ->for($race)
            ->for($race->championship)
            ->driver([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'licence_number' => $licence,
                'birth_date' => '2010-05-02',
            ])
            ->create();
    }
}

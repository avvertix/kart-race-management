<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BibReservation;
use App\Models\Championship;
use App\Models\Participant;
use App\Models\Race;
use App\Models\User;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class CopyChampionshipBibReservationsControllerTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_copy_form_requires_organizer_level(): void
    {
        $user = User::factory()->racemanager()->create();
        $championship = Championship::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('championships.bib-reservations.copy', $championship));

        $response->assertForbidden();
    }

    public function test_copy_form_lists_championships_with_reservations_or_participants(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        BibReservation::factory()->recycle($championship)->create();

        $withReservations = Championship::factory()->create();
        BibReservation::factory()->recycle($withReservations)->create();

        $withParticipants = Championship::factory()->create();
        Participant::factory()->for(Race::factory()->for($withParticipants))->for($withParticipants)->create();

        Championship::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('championships.bib-reservations.copy', $championship));

        $response->assertSuccessful();
        $response->assertViewIs('bib-reservation.copy');

        $this->assertEqualsCanonicalizing(
            [$withReservations->getKey(), $withParticipants->getKey()],
            $response->viewData('sourceChampionships')->modelKeys()
        );
    }

    public function test_store_copy_validates_input(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('championships.bib-reservations.store-copy', $championship), [
                'source_championship' => $championship->getKey(),
                'source' => 'invalid',
            ]);

        $response->assertSessionHasErrors(['source_championship', 'source']);
    }

    public function test_active_reservations_copied(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();
        $sourceChampionship = Championship::factory()->create();

        BibReservation::factory()->recycle($sourceChampionship)->withLicence('D0001')->create([
            'bib' => 10,
            'driver' => 'John Racer',
            'contact_email' => 'john@racer.local',
        ]);

        BibReservation::factory()->recycle($sourceChampionship)->create([
            'bib' => 11,
            'driver' => 'Jane Racer',
        ]);

        BibReservation::factory()->recycle($sourceChampionship)->withLicence('D0003')->expired()->create([
            'bib' => 12,
            'driver' => 'Expired Racer',
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('championships.bib-reservations.store-copy', $championship), [
                'source_championship' => $sourceChampionship->getKey(),
                'source' => 'reservations',
                'reservation_expiration_date' => now()->addMonth()->toDateString(),
            ]);

        $response->assertRedirect(route('championships.bib-reservations.index', $championship));
        $response->assertSessionHas('flash.banner', '2 reservations copied.');

        $reservations = $championship->reservations()->orderBy('bib')->get();

        $this->assertEquals([10, 11], $reservations->pluck('bib')->all());

        $this->assertEquals('John Racer', $reservations[0]->driver);
        $this->assertEquals('D0001', $reservations[0]->driver_licence);
        $this->assertEquals(hash('sha512', 'D0001'), $reservations[0]->driver_licence_hash);
        $this->assertEquals('john@racer.local', $reservations[0]->contact_email);
        $this->assertTrue($reservations[0]->reservation_expires_at->isSameDay(now()->addMonth()));

        $this->assertNull($reservations[1]->driver_licence_hash);

        $this->assertEquals(3, $sourceChampionship->reservations()->count());
    }

    public function test_reservations_created_from_unique_participants(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();
        $sourceChampionship = Championship::factory()->has(Race::factory()->count(2))->create();

        [$firstRace, $secondRace] = $sourceChampionship->races;

        foreach ([$firstRace, $secondRace] as $race) {
            Participant::factory()->for($race)->for($sourceChampionship)->driver([
                'first_name' => 'John',
                'last_name' => 'Racer',
                'licence_number' => 'D0001',
                'email' => 'john@racer.local',
                'bib' => 10,
            ])->create();
        }

        Participant::factory()->for($secondRace)->for($sourceChampionship)->driver([
            'first_name' => 'Jane',
            'last_name' => 'Racer',
            'licence_number' => 'D0002',
            'bib' => 20,
        ])->create();

        $response = $this
            ->actingAs($user)
            ->post(route('championships.bib-reservations.store-copy', $championship), [
                'source_championship' => $sourceChampionship->getKey(),
                'source' => 'participants',
            ]);

        $response->assertRedirect(route('championships.bib-reservations.index', $championship));

        $reservations = $championship->reservations()->orderBy('bib')->get();

        $this->assertEquals([10, 20], $reservations->pluck('bib')->all());

        $this->assertEquals('John Racer', $reservations[0]->driver);
        $this->assertEquals('D0001', $reservations[0]->driver_licence);
        $this->assertEquals(hash('sha512', 'D0001'), $reservations[0]->driver_licence_hash);
        $this->assertEquals('john@racer.local', $reservations[0]->contact_email);
        $this->assertNull($reservations[0]->reservation_expires_at);
    }

    public function test_bibs_outside_valid_range_skipped(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();
        $sourceChampionship = Championship::factory()->create();

        foreach ([0, 1, 999, 1000] as $bib) {
            BibReservation::factory()->recycle($sourceChampionship)->withLicence("D{$bib}")->create([
                'bib' => $bib,
                'driver' => "Racer {$bib}",
            ]);
        }

        $response = $this
            ->actingAs($user)
            ->post(route('championships.bib-reservations.store-copy', $championship), [
                'source_championship' => $sourceChampionship->getKey(),
                'source' => 'reservations',
            ]);

        $response->assertSessionHas('flash.banner', '2 reservations copied. Race numbers skipped due to conflicts or outside the allowed range: 0, 1000.');

        $this->assertEquals([1, 999], $championship->reservations()->orderBy('bib')->pluck('bib')->all());
    }

    public function test_conflicting_reservations_skipped(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();
        $sourceChampionship = Championship::factory()->create();

        BibReservation::factory()->recycle($championship)->withLicence('D0009')->create([
            'bib' => 10,
            'driver' => 'Existing Racer',
        ]);

        // Same bib already reserved in the target championship
        BibReservation::factory()->recycle($sourceChampionship)->withLicence('D0001')->create([
            'bib' => 10,
            'driver' => 'John Racer',
        ]);

        // Same licence already reserved in the target championship
        BibReservation::factory()->recycle($sourceChampionship)->withLicence('D0009')->create([
            'bib' => 11,
            'driver' => 'Renamed Racer',
        ]);

        BibReservation::factory()->recycle($sourceChampionship)->withLicence('D0002')->create([
            'bib' => 12,
            'driver' => 'Jane Racer',
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('championships.bib-reservations.store-copy', $championship), [
                'source_championship' => $sourceChampionship->getKey(),
                'source' => 'reservations',
            ]);

        $response->assertSessionHas('flash.banner', '1 reservations copied. Race numbers skipped due to conflicts or outside the allowed range: 10, 11.');

        $this->assertEquals([10, 12], $championship->reservations()->orderBy('bib')->pluck('bib')->all());
    }
}

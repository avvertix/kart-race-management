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

class BibReservationImportControllerTest extends TestCase
{
    use FastRefreshDatabase;

    public function test_import_form_requires_login(): void
    {
        $championship = Championship::factory()->create();

        $response = $this->get(route('championships.bib-reservations.import.create', $championship));

        $response->assertRedirectToRoute('login');
    }

    public function test_import_store_requires_login(): void
    {
        $championship = Championship::factory()->create();

        $response = $this->post(route('championships.bib-reservations.import.store', $championship));

        $response->assertRedirectToRoute('login');
    }

    public function test_import_form_requires_organizer_level(): void
    {
        $user = User::factory()->racemanager()->create();
        $championship = Championship::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('championships.bib-reservations.import.create', $championship));

        $response->assertForbidden();
    }

    public function test_import_form_shown(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('championships.bib-reservations.import.create', $championship));

        $response->assertSuccessful();
        $response->assertViewIs('bib-reservation.import');
        $response->assertViewHas('championship', $championship);
    }

    public function test_reservations_imported(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('championships.bib-reservations.import.store', $championship), [
                'reservations' => "1;Mario Rossi;DRV-0001;mario@example.com;\n2;Luigi Bianchi;DRV-0002;;",
            ]);

        $response->assertRedirectToRoute('championships.bib-reservations.index', $championship);
        $response->assertSessionHas('flash.banner', '2 reservations imported.');

        $this->assertDatabaseCount('bib_reservations', 2);

        $reservation = $championship->reservations()->where('bib', 1)->first();
        $this->assertNotNull($reservation);
        $this->assertEquals('Mario Rossi', $reservation->driver);
        $this->assertEquals('DRV-0001', $reservation->driver_licence);
        $this->assertEquals(hash('sha512', 'DRV-0001'), $reservation->driver_licence_hash);
        $this->assertEquals('mario@example.com', $reservation->contact_email);
        $this->assertNull($reservation->reservation_expires_at);
    }

    public function test_reservations_imported_with_expiration_date(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('championships.bib-reservations.import.store', $championship), [
                'reservations' => '1;Mario Rossi;DRV-0001;;2099-12-31',
            ]);

        $response->assertRedirectToRoute('championships.bib-reservations.index', $championship);

        $reservation = $championship->reservations()->where('bib', 1)->first();
        $this->assertNotNull($reservation->reservation_expires_at);
        $this->assertEquals('2099-12-31', $reservation->reservation_expires_at->toDateString());
    }

    public function test_import_fails_when_bib_missing(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('championships.bib-reservations.import.store', $championship), [
                'reservations' => ';Mario Rossi;DRV-0001;;',
            ]);

        $response->assertSessionHasErrors('reservations');
        $this->assertDatabaseCount('bib_reservations', 0);
    }

    public function test_import_fails_when_driver_name_missing(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('championships.bib-reservations.import.store', $championship), [
                'reservations' => '1;;DRV-0001;;',
            ]);

        $response->assertSessionHasErrors('reservations');
        $this->assertDatabaseCount('bib_reservations', 0);
    }

    public function test_import_fails_when_licence_missing(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('championships.bib-reservations.import.store', $championship), [
                'reservations' => '1;Mario Rossi;;;',
            ]);

        $response->assertSessionHasErrors('reservations');
        $this->assertDatabaseCount('bib_reservations', 0);
    }

    public function test_import_fails_when_bib_already_reserved(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        BibReservation::factory()->recycle($championship)->create(['bib' => 1]);

        $response = $this
            ->actingAs($user)
            ->post(route('championships.bib-reservations.import.store', $championship), [
                'reservations' => '1;Mario Rossi;DRV-0001;;',
            ]);

        $response->assertSessionHasErrors('reservations');
        $this->assertDatabaseCount('bib_reservations', 1);
    }

    public function test_import_fails_when_bib_already_assigned_to_participant(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();
        $race = Race::factory()->recycle($championship)->create();

        Participant::factory()->recycle([$championship, $race])->create(['bib' => 1]);

        $response = $this
            ->actingAs($user)
            ->post(route('championships.bib-reservations.import.store', $championship), [
                'reservations' => '1;Mario Rossi;DRV-0001;;',
            ]);

        $response->assertSessionHasErrors('reservations');
        $this->assertDatabaseCount('bib_reservations', 0);
    }

    public function test_import_fails_when_driver_already_has_reservation(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        BibReservation::factory()->recycle($championship)->create(['driver' => 'Mario Rossi']);

        $response = $this
            ->actingAs($user)
            ->post(route('championships.bib-reservations.import.store', $championship), [
                'reservations' => '2;Mario Rossi;DRV-0001;;',
            ]);

        $response->assertSessionHasErrors('reservations');
        $this->assertDatabaseCount('bib_reservations', 1);
    }

    public function test_import_fails_when_participant_with_same_licence_has_different_bib(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();
        $race = Race::factory()->recycle($championship)->create();

        Participant::factory()->recycle([$championship, $race])->create([
            'bib' => 5,
            'driver_licence' => hash('sha512', 'DRV-0001'),
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('championships.bib-reservations.import.store', $championship), [
                'reservations' => '1;Mario Rossi;DRV-0001;;',
            ]);

        $response->assertSessionHasErrors('reservations');
        $this->assertDatabaseCount('bib_reservations', 0);
    }

    public function test_import_skips_blank_lines(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('championships.bib-reservations.import.store', $championship), [
                'reservations' => "1;Mario Rossi;DRV-0001;;\n\n2;Luigi Bianchi;DRV-0002;;\n",
            ]);

        $response->assertRedirectToRoute('championships.bib-reservations.index', $championship);
        $this->assertDatabaseCount('bib_reservations', 2);
    }

    public function test_import_does_not_persist_any_row_when_one_line_is_invalid(): void
    {
        $user = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('championships.bib-reservations.import.store', $championship), [
                'reservations' => "1;Mario Rossi;DRV-0001;;\n;Luigi Bianchi;DRV-0002;;",
            ]);

        $response->assertSessionHasErrors('reservations');
        $this->assertDatabaseCount('bib_reservations', 0);
    }
}

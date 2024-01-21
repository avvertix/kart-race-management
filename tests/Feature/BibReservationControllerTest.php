<?php

namespace Tests\Feature;

use App\Models\BibReservation;
use App\Models\Championship;
use App\Models\DriverLicence;
use App\Models\Participant;
use App\Models\Race;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class BibReservationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_reservation_listing_requires_login(): void
    {
        $championship = Championship::factory()->create();

        $response = $this->get(route('championships.bib-reservations.index', $championship));

        $response->assertRedirectToRoute('login');
    }

    public function test_creating_reservation_requires_login(): void
    {
        $championship = Championship::factory()->create();

        $response = $this->get(route('championships.bib-reservations.create', $championship));

        $response->assertRedirectToRoute('login');
    }

    public function test_reservations_can_be_listed(): void
    {
        $user = User::factory()->racemanager()->create();

        $championship = Championship::factory()
            ->has(BibReservation::factory()->withLicence()->count(2), 'reservations')
            ->create();

        $response = $this
            ->actingAs($user)
            ->get(route('championships.bib-reservations.index', $championship));

        $response->assertSuccessful();

        $response->assertViewIs('bib-reservation.index');

        $response->assertViewHas('reservations', $championship->reservations()->orderBy('bib', 'ASC')->get());
    }

    public function test_creating_bib_reservation_requires_organizer_level(): void
    {
        $user = User::factory()->racemanager()->create();

        $championship = Championship::factory()
            ->create();

        $response = $this
            ->actingAs($user)
            ->get(route('championships.bib-reservations.create', $championship));

        $response->assertForbidden();
    }

    public function test_reservation_creation_form_shown(): void
    {
        $user = User::factory()->organizer()->create();

        $championship = Championship::factory()
            ->create();

        $response = $this
            ->actingAs($user)
            ->get(route('championships.bib-reservations.create', $championship));

        $response->assertSuccessful();

        $response->assertViewIs('bib-reservation.create');

        $response->assertViewHas('championship', $championship);

        $response->assertSee(__('Race Number'));
        
        $response->assertSee(__('Licence Number'));
    }
    
    public function test_bib_reservation_created(): void
    {
        $user = User::factory()->organizer()->create();

        $championship = Championship::factory()
            ->create();

        $response = $this
            ->actingAs($user)
            ->from(route('championships.bib-reservations.create', $championship))
            ->post(route('championships.bib-reservations.store', $championship), [
                'bib' => "100",
                'driver' => 'Driver name',
                'contact_email' => 'driver@local.host',
                'driver_licence_number' => 'LN1',
                'driver_licence_type' => DriverLicence::LOCAL_NATIONAL->value,
                'reservation_expiration_date' => null,
            ]);

        $response->assertRedirectToRoute('championships.bib-reservations.index', $championship);

        $response->assertSessionHas('flash.banner', 'Race number 100 reserved.');

        $reservation = BibReservation::first();

        $this->assertInstanceOf(BibReservation::class, $reservation);

        $this->assertTrue($reservation->championship->is($championship));

        $this->assertEquals("100", $reservation->bib);
        $this->assertEquals('Driver name', $reservation->driver);
        $this->assertEquals('driver@local.host', $reservation->contact_email);
        $this->assertEquals('LN1', $reservation->driver_licence);
        $this->assertEquals(hash('sha512', 'LN1'), $reservation->driver_licence_hash);
        $this->assertNull($reservation->reservation_expires_at);
    }
    
    public function test_bib_reservation_created_when_bib_used_in_other_championship(): void
    {
        $user = User::factory()->organizer()->create();

        $championship = Championship::factory()
            ->create();

        $race = Race::factory()
            ->recycle(Championship::factory()->create())
            ->has(Participant::factory()->state(['bib' => 100]), 'participants')
            ->create();

        $response = $this
            ->actingAs($user)
            ->from(route('championships.bib-reservations.create', $championship))
            ->post(route('championships.bib-reservations.store', $championship), [
                'bib' => "100",
                'driver' => 'Driver name',
                'contact_email' => 'driver@local.host',
                'driver_licence_number' => 'LN1',
                'driver_licence_type' => DriverLicence::LOCAL_NATIONAL->value,
                'reservation_expiration_date' => null,
            ]);

        $response->assertRedirectToRoute('championships.bib-reservations.index', $championship);

        $response->assertSessionHas('flash.banner', 'Race number 100 reserved.');

        $reservation = BibReservation::first();

        $this->assertInstanceOf(BibReservation::class, $reservation);

        $this->assertTrue($reservation->championship->is($championship));

        $this->assertEquals("100", $reservation->bib);
        $this->assertEquals('Driver name', $reservation->driver);
        $this->assertEquals('driver@local.host', $reservation->contact_email);
        $this->assertEquals('LN1', $reservation->driver_licence);
        $this->assertEquals(hash('sha512', 'LN1'), $reservation->driver_licence_hash);
        $this->assertNull($reservation->reservation_expires_at);
    }
    
    public function test_bib_reservation_created_without_driver_licence(): void
    {
        $user = User::factory()->organizer()->create();

        $championship = Championship::factory()
            ->create();

        $response = $this
            ->actingAs($user)
            ->from(route('championships.bib-reservations.create', $championship))
            ->post(route('championships.bib-reservations.store', $championship), [
                'bib' => "100",
                'driver' => 'Driver name',
                'contact_email' => 'driver@local.host',
            ]);

        $response->assertRedirectToRoute('championships.bib-reservations.index', $championship);

        $response->assertSessionHas('flash.banner', 'Race number 100 reserved.');

        $reservation = BibReservation::first();

        $this->assertInstanceOf(BibReservation::class, $reservation);

        $this->assertTrue($reservation->championship->is($championship));

        $this->assertEquals("100", $reservation->bib);
        $this->assertEquals('Driver name', $reservation->driver);
        $this->assertEquals('driver@local.host', $reservation->contact_email);
        $this->assertNull($reservation->driver_licence);
        $this->assertNull($reservation->driver_licence_hash);
        $this->assertNull($reservation->reservation_expires_at);
    }
    
    public function test_expiring_bib_reservation_created(): void
    {
        $user = User::factory()->organizer()->create();

        $championship = Championship::factory()
            ->create();

        $response = $this
            ->actingAs($user)
            ->from(route('championships.bib-reservations.create', $championship))
            ->post(route('championships.bib-reservations.store', $championship), [
                'bib' => "100",
                'driver' => 'Driver name',
                'contact_email' => 'driver@local.host',
                'driver_licence_number' => 'LN1',
                'driver_licence_type' => DriverLicence::LOCAL_NATIONAL->value,
                'reservation_expiration_date' => today()->addDays(8)->toDateString(),
            ]);

        $response->assertRedirectToRoute('championships.bib-reservations.index', $championship);

        $response->assertSessionHas('flash.banner', 'Race number 100 reserved.');

        $reservation = BibReservation::first();

        $this->assertInstanceOf(BibReservation::class, $reservation);

        $this->assertTrue($reservation->championship->is($championship));

        $this->assertEquals("100", $reservation->bib);
        $this->assertEquals('Driver name', $reservation->driver);
        $this->assertEquals('driver@local.host', $reservation->contact_email);
        $this->assertEquals('LN1', $reservation->driver_licence);
        $this->assertEquals(hash('sha512', 'LN1'), $reservation->driver_licence_hash);
        $this->assertTrue($reservation->reservation_expires_at->isEndOfDay());
        $this->assertTrue($reservation->reservation_expires_at->isSameDay(today()->addDays(8)->endOfDay()));
    }

    public function test_reservation_not_created_when_bib_already_reserved(): void
    {
        $user = User::factory()->organizer()->create();

        $championship = Championship::factory()
            ->has(BibReservation::factory()->state(['bib' => 100]), 'reservations')
            ->create();

        $response = $this
            ->actingAs($user)
            ->from(route('championships.bib-reservations.create', $championship))
            ->post(route('championships.bib-reservations.store', $championship), [
                'bib' => "100",
                'driver' => 'Driver name',
                'contact_email' => 'driver@local.host',
                'driver_licence_number' => 'LN1',
                'driver_licence_type' => DriverLicence::LOCAL_NATIONAL->value,
                'reservation_expiration_date' => null,
            ]);

        $response->assertRedirectToRoute('championships.bib-reservations.create', $championship);

        $response->assertSessionHasErrors('bib');
    }

    public function test_reservation_not_created_when_bib_already_used_in_championship(): void
    {
        $user = User::factory()->organizer()->create();

        $championship = Championship::factory()
            ->create();

        $race = Race::factory()
            ->recycle($championship)
            ->has(Participant::factory()->state(['bib' => 100]), 'participants')
            ->create();

        $response = $this
            ->actingAs($user)
            ->from(route('championships.bib-reservations.create', $championship))
            ->post(route('championships.bib-reservations.store', $championship), [
                'bib' => "100",
                'driver' => 'Driver name',
                'contact_email' => 'driver@local.host',
                'driver_licence_number' => 'LN1',
                'driver_licence_type' => DriverLicence::LOCAL_NATIONAL->value,
                'reservation_expiration_date' => null,
            ]);

        $response->assertRedirectToRoute('championships.bib-reservations.create', $championship);

        $response->assertSessionHasErrors('bib');
    }

    public function test_reservation_details_shown(): void
    {
        $user = User::factory()->organizer()->create();

        $reservation = BibReservation::factory()
            ->create();

        $response = $this
            ->actingAs($user)
            ->get(route('bib-reservations.show', $reservation));

        $response->assertSuccessful();

        $response->assertViewIs('bib-reservation.show');

        $response->assertViewHas('reservation', $reservation);

        $response->assertViewHas('championship', $reservation->championship);
    }

    public function test_reservation_edit_page_rendered_when_missing_licence(): void
    {
        $user = User::factory()->organizer()->create();

        $reservation = BibReservation::factory()
            ->create();

        $response = $this
            ->actingAs($user)
            ->get(route('bib-reservations.edit', $reservation));

        $response->assertSuccessful();

        $response->assertViewIs('bib-reservation.edit');

        $response->assertViewHas('reservation', $reservation);

        $response->assertViewHas('championship', $reservation->championship);
    }

    public function test_reservation_edit_page_rendered(): void
    {
        $user = User::factory()->organizer()->create();

        $reservation = BibReservation::factory()
            ->withLicence()
            ->expired()
            ->create();

        $response = $this
            ->actingAs($user)
            ->get(route('bib-reservations.edit', $reservation));

        $response->assertSuccessful();

        $response->assertViewIs('bib-reservation.edit');

        $response->assertViewHas('reservation', $reservation);

        $response->assertViewHas('championship', $reservation->championship);
    }

    public function test_reservation_updated(): void
    {
        $user = User::factory()->organizer()->create();

        $reservation = BibReservation::factory()
            ->create([
                'bib' => "100",
                'driver' => 'Driver name',
                'driver_licence_hash' => hash('sha512', 'LN1'),
                'driver_licence' => 'LN1',
            ]);

        $response = $this
            ->actingAs($user)
            ->from(route('bib-reservations.edit', $reservation))
            ->put(route('bib-reservations.update', $reservation), [
                'bib' => "100",
                'driver' => 'Driver name',
                'contact_email' => 'driver@local.host',
                'driver_licence_number' => 'LN1',
                'driver_licence_type' => DriverLicence::LOCAL_NATIONAL->value,
                'reservation_expiration_date' => null,
            ]);

        $response->assertRedirectToRoute('championships.bib-reservations.index', $reservation->championship);

        $response->assertSessionHas('flash.banner', 'Reservation for 100 updated.');

        $updatedReservation = $reservation->fresh();

        $this->assertInstanceOf(BibReservation::class, $updatedReservation);

        $this->assertEquals("100", $updatedReservation->bib);
        $this->assertEquals('Driver name', $updatedReservation->driver);
        $this->assertEquals('driver@local.host', $updatedReservation->contact_email);
        $this->assertEquals('LN1', $updatedReservation->driver_licence);
        $this->assertEquals(hash('sha512', 'LN1'), $updatedReservation->driver_licence_hash);
        $this->assertNull($updatedReservation->reservation_expires_at);
    }
    
    public function test_bib_editable_when_not_yet_used(): void
    {
        $user = User::factory()->organizer()->create();

        $reservation = BibReservation::factory()
            ->create([
                'bib' => "100",
                'driver' => 'Driver name',
            ]);

        $response = $this
            ->actingAs($user)
            ->from(route('bib-reservations.edit', $reservation))
            ->put(route('bib-reservations.update', $reservation), [
                'bib' => "101",
                'driver' => 'Driver name',
                'contact_email' => 'driver@local.host',
                'driver_licence_number' => 'LN1',
                'driver_licence_type' => DriverLicence::LOCAL_NATIONAL->value,
                'reservation_expiration_date' => null,
            ]);

        $response->assertRedirectToRoute('championships.bib-reservations.index', $reservation->championship);

        $response->assertSessionHas('flash.banner', 'Reservation for 101 updated.');

        $updatedReservation = $reservation->fresh();

        $this->assertInstanceOf(BibReservation::class, $updatedReservation);

        $this->assertEquals("101", $updatedReservation->bib);
        $this->assertEquals('Driver name', $updatedReservation->driver);
        $this->assertEquals('driver@local.host', $updatedReservation->contact_email);
        $this->assertEquals('LN1', $updatedReservation->driver_licence);
        $this->assertEquals(hash('sha512', 'LN1'), $updatedReservation->driver_licence_hash);
        $this->assertNull($updatedReservation->reservation_expires_at);
    }
    
    public function test_bib_not_changed_when_reservation_already_used_using_licence(): void
    {
        $user = User::factory()->organizer()->create();

        $championship = Championship::factory()
            ->create();

        $race = Race::factory()
            ->recycle($championship)
            ->has(Participant::factory()->driver([
                'bib' => 100,
                'licence_number' => 'LN1',
            ]), 'participants')
            ->create();

        $reservation = BibReservation::factory()
            ->recycle($championship)
            ->create([
                'bib' => "100",
                'driver' => 'Driver name',
            ]);

// l'idea è di prevenire che in validazione possa essere utilizzata una licenza assegnata ad un altro concorrente 
// che potrebbe avere lo stesso pettorale o che potrebbe causare la modifica del pettorale

        $response = $this
            ->actingAs($user)
            ->from(route('bib-reservations.edit', $reservation))
            ->put(route('bib-reservations.update', $reservation), [
                'bib' => "101",
                'driver' => 'Driver name',
                'contact_email' => 'driver@local.host',
                'driver_licence_number' => 'LN1',
                'driver_licence_type' => DriverLicence::LOCAL_NATIONAL->value,
                'reservation_expiration_date' => null,
            ]);

        $response->assertRedirectToRoute('bib-reservations.edit', $reservation);

        $response->assertSessionHasErrors(['bib' => __('Participant with same licence has the race number 100.')]);
    }

    public function test_remove_licence_not_possible_when_updating_reservation(): void
    {
        $user = User::factory()->organizer()->create();

        $reservation = BibReservation::factory()
            ->withLicence()
            ->create([
                'bib' => "100",
                'driver' => 'Driver name',
            ]);

        $response = $this
            ->actingAs($user)
            ->from(route('bib-reservations.edit', $reservation))
            ->put(route('bib-reservations.update', $reservation), [
                'bib' => "100",
                'driver' => 'Driver name',
                'contact_email' => 'driver@local.host',
                'driver_licence_number' => null,
                'driver_licence_type' => DriverLicence::LOCAL_NATIONAL->value,
                'reservation_expiration_date' => null,
            ]);

        $response->assertRedirectToRoute('bib-reservations.edit', $reservation);

        $response->assertSessionHasErrors(['driver_licence_number' => __('Removing licence not allowed.')]);
    }
    
    public function test_reservation_destroyed(): void
    {
        $user = User::factory()->organizer()->create();

        $reservation = BibReservation::factory()
            ->withLicence()
            ->create([
                'bib' => "100",
                'driver' => 'Driver name',
            ]);

        $championship = $reservation->championship;

        $response = $this
            ->actingAs($user)
            ->from(route('bib-reservations.edit', $reservation))
            ->delete(route('bib-reservations.destroy', $reservation));

        $response->assertRedirectToRoute('championships.bib-reservations.index', $championship);

        $response->assertSessionHas('flash.banner', 'Reservation for 100 removed.');

        $this->assertNull($reservation->fresh());
    }

}

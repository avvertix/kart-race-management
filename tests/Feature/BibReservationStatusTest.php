<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\CreateBibReservation;
use App\Actions\GenerateRaceNumber;
use App\Actions\RegisterParticipant;
use App\Actions\Wildcard\AttributeWildcardBasedOnBibReservation;
use App\Enums\BibReservationStatus;
use App\Features\LicencesAndProducts;
use App\Models\BibReservation;
use App\Models\Category;
use App\Models\Championship;
use App\Models\DriverLicence;
use App\Models\Participant;
use App\Models\Race;
use App\Models\User;
use App\Models\WildcardStrategy;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Laravel\Pennant\Feature;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\CreateDriver;
use Tests\CreateVehicle;
use Tests\EnablesLicencesAndProducts;
use Tests\TestCase;

class BibReservationStatusTest extends TestCase
{
    use CreateDriver;
    use CreateVehicle;
    use EnablesLicencesAndProducts;
    use FastRefreshDatabase;

    public function test_reservations_are_confirmed_by_default(): void
    {
        $reservation = BibReservation::factory()->create();

        $this->assertSame(BibReservationStatus::Confirmed, $reservation->fresh()->status);
    }

    public function test_the_organizer_creates_confirmed_reservations(): void
    {
        $organizer = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        $this->actingAs($organizer)
            ->post(route('championships.bib-reservations.store', $championship), [
                'bib' => '10',
                'driver' => 'Driver name',
                'driver_licence_number' => 'LN1',
            ])
            ->assertSessionHasNoErrors();

        $reservation = $championship->reservations()->sole();

        $this->assertSame(BibReservationStatus::Confirmed, $reservation->status);
        $this->assertTrue($reservation->createdBy->is($organizer));
    }

    public function test_a_pending_reservation_blocks_the_number_for_other_licences(): void
    {
        $race = $this->raceOpenForRegistration();

        BibReservation::factory()->pending()->recycle($race->championship)->withLicence('D0002')->create(['bib' => 100]);

        try {
            $this->register($race, 100);

            $this->fail('Expected ValidationException. Nothing thrown.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('bib', $exception->errors());
        }
    }

    public function test_a_released_reservation_frees_the_number(): void
    {
        $race = $this->raceOpenForRegistration();

        BibReservation::factory()->released()->recycle($race->championship)->withLicence('D0002')->create(['bib' => 100]);
        BibReservation::factory()->released()->recycle($race->championship)->withLicence('D0001')->create(['bib' => 1]);

        $this->assertContains(1, (new GenerateRaceNumber())($race->championship));

        $participant = $this->register($race, 100);

        $this->assertSame(100, (int) $participant->bib);
    }

    public function test_a_released_number_can_be_reserved_again(): void
    {
        $championship = Championship::factory()->create();

        BibReservation::factory()->released()->recycle($championship)->create(['bib' => 10, 'driver' => 'Driver name']);

        $reservation = (new CreateBibReservation())($championship, [
            'bib' => 10,
            'driver' => 'Driver name',
            'driver_licence_number' => 'LN2',
            'contact_email' => null,
            'reservation_expiration_date' => null,
        ]);

        $this->assertSame(BibReservationStatus::Confirmed, $reservation->status);
    }

    public function test_a_released_number_can_be_imported_again(): void
    {
        $organizer = User::factory()->organizer()->create();
        $championship = Championship::factory()->create();

        BibReservation::factory()->released()->recycle($championship)->create(['bib' => 10, 'driver' => 'Driver name']);

        $this->actingAs($organizer)
            ->post(route('championships.bib-reservations.import.store', $championship), [
                'reservations' => '10;Driver name;LN2',
            ])
            ->assertSessionHasNoErrors();

        $imported = $championship->reservations()->notReleased()->sole();

        $this->assertSame(BibReservationStatus::Confirmed, $imported->fresh()->status);
        $this->assertTrue($imported->createdBy->is($organizer));
    }

    public function test_a_released_reservation_does_not_spare_the_wildcard(): void
    {
        $championship = Championship::factory()->has(Race::factory())->create([
            'wildcard' => ['enabled' => true, 'strategy' => WildcardStrategy::BASED_ON_BIB_RESERVATION],
        ]);
        $race = $championship->races->first();

        BibReservation::factory()->released()->recycle($championship)->withLicence('LN1')->create(['bib' => 100]);

        $participant = Participant::factory()->recycle($championship)->recycle($race)
            ->driver(['bib' => 100, 'licence_number' => 'LN1'])
            ->create();

        $this->assertTrue((new AttributeWildcardBasedOnBibReservation)($participant, $race));
    }

    public function test_the_organizer_confirms_a_pending_reservation(): void
    {
        $reservation = BibReservation::factory()->pending()->create();

        $this->actingAs(User::factory()->organizer()->create())
            ->post(route('bib-reservations.confirm', $reservation))
            ->assertRedirect(route('bib-reservations.show', $reservation));

        $this->assertSame(BibReservationStatus::Confirmed, $reservation->fresh()->status);

        $this->actingAs(User::factory()->organizer()->create())
            ->post(route('bib-reservations.confirm', $reservation))
            ->assertForbidden();
    }

    public function test_the_organizer_releases_a_reservation(): void
    {
        $organizer = User::factory()->organizer()->create();
        $reservation = BibReservation::factory()->withLicence()->create();

        $this->actingAs($organizer)
            ->get(route('bib-reservations.show', $reservation))
            ->assertSee('Release');

        $this->actingAs($organizer)
            ->post(route('bib-reservations.release', $reservation))
            ->assertRedirect(route('bib-reservations.show', $reservation));

        $reservation = $reservation->fresh();

        $this->assertSame(BibReservationStatus::Released, $reservation->status);
        $this->assertNotNull($reservation->released_at);

        $this->actingAs($organizer)
            ->get(route('championships.bib-reservations.index', $reservation->championship))
            ->assertSee('Released');

        $this->actingAs($organizer)
            ->get(route('bib-reservations.edit', $reservation))
            ->assertForbidden();

        $this->actingAs($organizer)
            ->post(route('bib-reservations.release', $reservation))
            ->assertForbidden();
    }

    public function test_changing_the_status_needs_an_organizer_with_early_access(): void
    {
        $reservation = BibReservation::factory()->pending()->create();

        $this->actingAs(User::factory()->racemanager()->create())
            ->post(route('bib-reservations.release', $reservation))
            ->assertForbidden();

        Feature::define(LicencesAndProducts::class, fn () => false);

        $this->actingAs(User::factory()->organizer()->create())
            ->post(route('bib-reservations.confirm', $reservation))
            ->assertNotFound();

        $this->assertSame(BibReservationStatus::Pending, $reservation->fresh()->status);
    }

    public function test_a_legacy_reservation_above_the_maximum_can_be_edited(): void
    {
        $reservation = BibReservation::factory()->create(['bib' => 1200, 'driver' => 'Driver name']);
        $organizer = User::factory()->organizer()->create();

        $input = [
            'driver' => 'Renamed driver',
            'contact_email' => 'driver@local.host',
            'driver_licence_number' => 'LN1',
            'driver_licence_type' => DriverLicence::LOCAL_NATIONAL->value,
            'reservation_expiration_date' => null,
        ];

        $this->actingAs($organizer)
            ->put(route('bib-reservations.update', $reservation), ['bib' => '1200', ...$input])
            ->assertSessionHasNoErrors();

        $this->assertSame('Renamed driver', $reservation->fresh()->driver);

        $this->actingAs($organizer)
            ->put(route('bib-reservations.update', $reservation), ['bib' => '1201', ...$input])
            ->assertSessionHasErrors('bib');

        $this->assertSame(1200, (int) $reservation->fresh()->bib);
    }

    private function raceOpenForRegistration(): Race
    {
        Notification::fake();

        $race = Race::factory()->create();

        $this->travelTo($race->registration_closes_at->subHour());

        return $race;
    }

    private function register(Race $race, int $bib): Participant
    {
        return app(RegisterParticipant::class)($race, [
            'bib' => $bib,
            'category' => Category::factory()->recycle($race->championship)->create()->ulid,
            ...$this->generateValidDriver(),
            ...$this->generateValidVehicle(),
            'consent_privacy' => true,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BibReservationStatus;
use App\Livewire\RaceNumberPicker;
use App\Models\BibReservation;
use App\Models\Championship;
use App\Models\Enrollment;
use App\Models\Licence;
use App\Models\Participant;
use App\Models\Race;
use App\Models\User;
use Database\Factories\LicenceFactory;
use Livewire\Livewire;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\EnablesLicencesAndProducts;
use Tests\TestCase;

class PreviousNumbersTest extends TestCase
{
    use EnablesLicencesAndProducts;
    use FastRefreshDatabase;

    public function test_the_organizer_sees_the_numbers_the_driver_used_before(): void
    {
        $holder = LicenceFactory::holder('Mario', 'Rossi');
        $holder->birth_date = '2001-03-04';
        $driver = User::factory()->create();
        $licence = Licence::factory()->verified()->for($driver)->create(['number' => 'NEW-2026', 'holder' => $holder]);
        $enrollment = Enrollment::factory()->recycle($this->championship())->for($driver)->create(['licence_id' => $licence->getKey()]);

        $byLicence = Championship::factory()->create(['title' => 'Same licence championship']);
        $races = Race::factory()->count(2)->recycle($byLicence)->create();
        foreach ($races as $race) {
            Participant::factory()->recycle($byLicence)->recycle($race)->driver(['bib' => 11, 'licence_number' => 'NEW-2026', 'first_name' => 'Mario', 'last_name' => 'Rossi'])->create();
        }

        $byName = Championship::factory()->create(['title' => 'Old licence championship']);
        Participant::factory()->recycle($byName)->driver(['bib' => 22, 'licence_number' => 'OLD-2020', 'first_name' => ' mario', 'last_name' => 'ROSSI ', 'birth_date' => '2001-03-04', 'email' => 'old-licence@example.com'])->create();

        $byUser = Championship::factory()->create(['title' => 'Added by user championship']);
        Participant::factory()->recycle($byUser)->create(['bib' => 33, 'added_by' => $driver->getKey(), 'first_name' => 'Mario', 'last_name' => 'Rossi']);

        $other = Championship::factory()->create(['title' => 'Namesake championship']);
        Participant::factory()->recycle($other)->driver(['bib' => 44, 'licence_number' => 'OTHER', 'first_name' => 'Mario', 'last_name' => 'Rossi', 'birth_date' => '1990-01-01', 'email' => 'namesake@example.com'])->create();

        $this->actingAs(User::factory()->organizer()->create())
            ->get(route('enrollments.show', $enrollment))
            ->assertSuccessful()
            ->assertSee(__('Previous numbers'))
            ->assertSeeInOrder(['11', 'Same licence championship', '2 races'])
            ->assertSee('Old licence championship')
            ->assertSee('Added by user championship')
            ->assertDontSee('Namesake championship')
            ->assertDontSee('namesake@example.com')
            ->assertDontSee('old-licence@example.com')
            ->assertDontSee('OLD-2020')
            ->assertDontSee('2001-03-04');
    }

    public function test_a_number_found_is_used_when_free(): void
    {
        $championship = $this->championship();
        $licence = Licence::factory()->verified()->create(['number' => 'L-1']);
        $enrollment = Enrollment::factory()->recycle($championship)->create(['licence_id' => $licence->getKey()]);
        $past = Championship::factory()->create();
        Participant::factory()->recycle($past)->driver(['bib' => 11, 'licence_number' => 'L-1'])->create();
        Participant::factory()->recycle($past)->driver(['bib' => 12, 'licence_number' => 'L-1'])->create();
        BibReservation::factory()->recycle($championship)->create(['bib' => 12]);

        $this->actingAs(User::factory()->organizer()->create())
            ->get(route('enrollments.show', $enrollment))
            ->assertSee(__('Use number :bib', ['bib' => 11]))
            ->assertDontSee(__('Use number :bib', ['bib' => 12]));
    }

    public function test_the_organizer_confirms_the_pending_number_from_the_enrollment(): void
    {
        $enrollment = Enrollment::factory()->recycle($this->championship())->create();
        $reservation = BibReservation::factory()->pending()->recycle($enrollment->championship)->create(['enrollment_id' => $enrollment->getKey(), 'bib' => 9]);

        $this->actingAs(User::factory()->organizer()->create())
            ->post(route('bib-reservations.confirm', $reservation), ['return' => 'enrollment'])
            ->assertRedirect(route('enrollments.show', $enrollment));

        $this->assertSame(BibReservationStatus::Confirmed, $reservation->fresh()->status);
    }

    public function test_a_licence_with_a_copied_reservation_sees_its_number(): void
    {
        $championship = $this->championship();
        $driver = User::factory()->create();
        $licence = Licence::factory()->for($driver)->create(['number' => 'COPIED-1']);
        BibReservation::factory()->recycle($championship)->withLicence('COPIED-1')->create(['bib' => 5]);
        $enrollment = Enrollment::factory()->recycle($championship)->for($driver)->create(['licence_id' => $licence->getKey()]);

        Livewire::actingAs($driver)
            ->test(RaceNumberPicker::class, ['championship' => $championship])
            ->assertSee(__('Number :bib is reserved for :driver.', ['bib' => 5, 'driver' => $licence->holder->fullName()]));

        Livewire::actingAs($driver)
            ->test(RaceNumberPicker::class, ['championship' => $championship, 'enrollment' => $enrollment])
            ->assertSee(__('Number :bib is reserved for :driver.', ['bib' => 5, 'driver' => $licence->holder->fullName()]));
    }

    private function championship(): Championship
    {
        return Championship::factory()->openForEnrollment(reservesRaceNumber: true)->create();
    }
}

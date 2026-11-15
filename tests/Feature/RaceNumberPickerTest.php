<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Enrollments\PickRaceNumber;
use App\Actions\LicenceRequests\EmitLicence;
use App\Enums\BibReservationStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\LicenceRequestPaymentStatus;
use App\Enums\RaceNumberState;
use App\Livewire\FreeRaceNumbers;
use App\Models\BibReservation;
use App\Models\Championship;
use App\Models\Enrollment;
use App\Models\Licence;
use App\Models\LicenceRequest;
use App\Models\Participant;
use App\Models\User;
use App\View\Components\RaceNumberPicker;
use Database\Factories\LicenceFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\EnablesLicencesAndEnrollments;
use Tests\TestCase;

class RaceNumberPickerTest extends TestCase
{
    use EnablesLicencesAndEnrollments;
    use FastRefreshDatabase;

    public function test_the_picker_shows_the_hundreds_and_the_state_of_each_number(): void
    {
        $championship = $this->championship();
        BibReservation::factory()->recycle($championship)->create(['bib' => 7, 'driver' => 'Reserved Holder']);
        BibReservation::factory()->released()->recycle($championship)->create(['bib' => 8]);
        Participant::factory()->recycle($championship)->create(['bib' => 9, 'first_name' => 'Used', 'last_name' => 'Holder']);

        $this->actingAs(User::factory()->create());

        $picker = new RaceNumberPicker($championship);

        $this->assertSame(1000, mb_strlen($picker->states));
        $this->assertSame(['r', 'f', 'u', 'f'], [$picker->states[7], $picker->states[8], $picker->states[9], $picker->states[777]]);
        $this->assertSame([], $picker->holders);

        $this->blade('<x-race-number-picker :championship="$championship" />', ['championship' => $championship])
            ->assertSee('1–99')
            ->assertSee('900–999')
            ->assertSee(__(':count free', ['count' => 97]))
            ->assertSee(__('Pick a free number.'))
            ->assertSee('name="bib"', escape: false)
            ->assertDontSee('Reserved Holder')
            ->assertDontSee('Used Holder');
    }

    public function test_the_current_pick_opens_its_hundred(): void
    {
        $championship = $this->championship();
        $driver = User::factory()->create();
        $enrollment = Enrollment::factory()->recycle($championship)->for($driver)->create();
        app(PickRaceNumber::class)($enrollment, 777, $driver);

        $this->actingAs($driver);

        $picker = new RaceNumberPicker($championship, $enrollment);

        $this->assertSame(777, $picker->selected);
        $this->assertSame(7, $picker->hundred);
        $this->assertSame('f', $picker->states[777]);

        $this->blade('<x-race-number-picker :championship="$championship" :enrollment="$enrollment" />', ['championship' => $championship, 'enrollment' => $enrollment])
            ->assertSee('name="bib" value="777"', escape: false);
    }

    public function test_organizers_also_see_who_holds_each_number(): void
    {
        $championship = $this->championship();
        BibReservation::factory()->recycle($championship)->create(['bib' => 7, 'driver' => 'Reserved Holder']);

        $this->actingAs(User::factory()->organizer()->create());
        $this->assertSame([7 => 'Reserved Holder'], (new RaceNumberPicker($championship, showHolders: true))->holders);

        $this->actingAs(User::factory()->create());
        $this->assertSame([], (new RaceNumberPicker($championship, showHolders: true))->holders);
    }

    public function test_the_numbers_used_before_are_suggested(): void
    {
        $championship = $this->championship();
        $driver = User::factory()->create();
        $past = Championship::factory()->create(['title' => 'Past championship']);
        Participant::factory()->recycle($past)->create(['bib' => 44, 'claimed_by' => $driver->getKey(), 'first_name' => 'Mario', 'last_name' => 'Rossi']);
        Participant::factory()->recycle($past)->create(['bib' => 55, 'added_by' => $driver->getKey()]);
        Participant::factory()->recycle($past)->create(['bib' => 66, 'first_name' => 'Other', 'last_name' => 'Driver']);
        Participant::factory()->recycle($past)->create(['bib' => 84, 'claimed_by' => $driver->getKey(), 'first_name' => 'Andre', 'last_name' => 'Martinho']);
        BibReservation::factory()->recycle($championship)->create(['bib' => 55, 'driver' => 'Someone Else']);
        BibReservation::factory()->recycle($championship)->create(['bib' => 84, 'driver' => 'Andre Martinho']);

        $this->actingAs($driver);

        $previous = (new RaceNumberPicker($championship))->previousNumbers->keyBy('bib');

        $this->assertSame([RaceNumberState::Free, null], [$previous[44]['state'], $previous[44]['note']]);
        $this->assertSame([RaceNumberState::Reserved, __('Taken by another driver.')], [$previous[55]['state'], $previous[55]['note']]);
        $this->assertSame(__('Reserved for :driver.', ['driver' => 'Andre Martinho']), $previous[84]['note']);

        $this->blade('<x-race-number-picker :championship="$championship" />', ['championship' => $championship])
            ->assertSee(__('Numbers you used before'))
            ->assertSee('Past championship')
            ->assertSee('Mario Rossi')
            ->assertDontSee('Other Driver');
    }

    public function test_the_picked_number_is_held_for_the_enrollment(): void
    {
        $championship = $this->championship();
        $driver = User::factory()->create();
        $licence = Licence::factory()->for($driver)->create(['number' => 'D-777']);

        $this->actingAs($driver)
            ->get(route('public.championships.enrollment', $championship))
            ->assertSee(__('your pick'));

        $this->actingAs($driver)
            ->post(route('public.championships.enrollment.store', $championship), ['subject' => 'licence:'.$licence->ulid, 'bib' => '77'])
            ->assertSessionHasNoErrors();

        $reservation = $championship->enrollments()->sole()->reservations()->sole();

        $this->assertSame(77, (int) $reservation->bib);
        $this->assertSame(BibReservationStatus::Pending, $reservation->status);
        $this->assertSame(Licence::hashNumber('D-777'), $reservation->driver_licence_hash);
        $this->assertSame($licence->getKey(), $reservation->licence_id);
        $this->assertTrue($reservation->createdBy->is($driver));
    }

    public function test_two_drivers_cannot_hold_the_same_number(): void
    {
        $championship = $this->championship();
        BibReservation::factory()->pending()->recycle($championship)->create([
            'enrollment_id' => Enrollment::factory()->recycle($championship)->create()->getKey(),
            'bib' => 77,
        ]);

        $driver = User::factory()->create();

        $this->actingAs($driver)
            ->post(route('public.championships.enrollment.store', $championship), ['subject' => 'licence:'.Licence::factory()->for($driver)->create(['holder' => LicenceFactory::holder('Luca', 'Bianchi')])->ulid, 'bib' => '77'])
            ->assertSessionHasErrors(['bib' => __('Race number :bib is not available anymore, pick another one.', ['bib' => 77])]);

        $this->assertSame(1, $championship->enrollments()->count());
    }

    public function test_the_driver_changes_the_pick_until_confirmation(): void
    {
        $championship = $this->championship();
        $driver = User::factory()->create();
        $enrollment = Enrollment::factory()->recycle($championship)->for($driver)->create();

        $this->actingAs($driver)
            ->get(route('my-enrollments.show', $enrollment))
            ->assertSee(__('Pick your race number'));

        $this->actingAs($driver)
            ->get(route('my-enrollments.race-number.edit', $enrollment))
            ->assertSuccessful()
            ->assertSee(__('your pick'));

        $this->actingAs($driver)
            ->put(route('my-enrollments.race-number.update', $enrollment), ['bib' => '12'])
            ->assertRedirect(route('my-enrollments.show', $enrollment));

        $this->actingAs($driver)
            ->put(route('my-enrollments.race-number.update', $enrollment), ['bib' => '13'])
            ->assertSessionHasNoErrors();

        $this->assertSame([13], $enrollment->reservations()->pluck('bib')->map(fn ($bib) => (int) $bib)->all());

        $this->actingAs($driver)
            ->get(route('my-enrollments.show', $enrollment))
            ->assertDontSee(__('Pick your race number'));

        $enrollment->update(['status' => EnrollmentStatus::Confirmed]);

        $this->actingAs($driver)
            ->put(route('my-enrollments.race-number.update', $enrollment), ['bib' => '14'])
            ->assertForbidden();

        $this->assertSame(13, (int) $enrollment->reservations()->sole()->bib);
    }

    public function test_a_confirmed_enrollment_keeps_its_number(): void
    {
        $enrollment = Enrollment::factory()->confirmed()->recycle($this->championship())->create();

        $this->expectException(ValidationException::class);

        app(PickRaceNumber::class)($enrollment, 15, User::factory()->organizer()->create());
    }

    public function test_the_organizer_picks_the_number_for_the_driver(): void
    {
        $enrollment = Enrollment::factory()->recycle($this->championship())->create();

        $this->actingAs(User::factory()->organizer()->create())
            ->put(route('enrollments.race-number.update', $enrollment), ['bib' => '31'])
            ->assertRedirect(route('enrollments.show', $enrollment));

        $this->assertSame(31, (int) $enrollment->reservations()->sole()->bib);
    }

    public function test_a_licence_with_a_reserved_number_keeps_it(): void
    {
        $championship = $this->championship();
        $driver = User::factory()->create();
        $licence = Licence::factory()->for($driver)->create(['number' => 'COPIED-1']);
        BibReservation::factory()->recycle($championship)->withLicence('COPIED-1')->create(['bib' => 5]);

        $this->actingAs($driver)
            ->post(route('public.championships.enrollment.store', $championship), ['subject' => 'licence:'.$licence->ulid, 'bib' => '77'])
            ->assertSessionHasNoErrors();

        $enrollment = $championship->enrollments()->sole();

        $this->assertSame(0, $enrollment->reservations()->count());

        $this->actingAs($driver)
            ->put(route('my-enrollments.race-number.update', $enrollment), ['bib' => '77'])
            ->assertSessionHasErrors(['bib' => __('The licence already has race number :bib reserved.', ['bib' => 5])]);
    }

    public function test_the_picker_stays_within_the_championship_numbers(): void
    {
        $this->actingAs(User::factory()->create());

        $picker = new RaceNumberPicker($this->championship());

        $this->assertSame(range(0, 9), array_column($picker->hundreds, 'hundred'));
        $this->assertSame([1, 999], [$picker->hundreds[0]['from'], $picker->hundreds[9]['to']]);
        $this->assertSame(1000, mb_strlen($picker->states));
    }

    public function test_a_number_is_picked_only_when_enrollment_grants_it(): void
    {
        $championship = $this->championship(grantsReservation: false);
        $driver = User::factory()->create();

        $this->actingAs($driver)
            ->get(route('public.championships.enrollment', $championship))
            ->assertDontSee(__('your pick'));

        $this->actingAs($driver)
            ->post(route('public.championships.enrollment.store', $championship), ['subject' => 'licence:'.Licence::factory()->for($driver)->create(['holder' => LicenceFactory::holder('Luca', 'Bianchi')])->ulid, 'bib' => '77'])
            ->assertSessionHasNoErrors();

        $this->assertSame(0, BibReservation::query()->count());
    }

    public function test_a_number_picked_while_waiting_for_a_request_gets_the_emitted_licence(): void
    {
        Notification::fake();

        $championship = $this->championship();
        $licenceRequest = LicenceRequest::factory()->verified()->create(['payment_status' => LicenceRequestPaymentStatus::Paid]);

        $this->actingAs($licenceRequest->user)
            ->post(route('public.championships.enrollment.store', $championship), ['subject' => 'licence_request:'.$licenceRequest->ulid, 'bib' => '21'])
            ->assertSessionHasNoErrors();

        $licence = app(EmitLicence::class)($licenceRequest, User::factory()->organizer()->create(), 'A777777', now()->year, UploadedFile::fake()->create('licence.pdf', 100, 'application/pdf'));

        $reservation = BibReservation::query()->sole();

        $this->assertSame($licence->getKey(), $reservation->licence_id);
        $this->assertSame(Licence::hashNumber('A777777'), $reservation->driver_licence_hash);
        $this->assertSame('A777777', $reservation->driver_licence);
    }

    public function test_the_organizer_sees_the_holder_of_a_reserved_number(): void
    {
        $championship = Championship::factory()->create();
        BibReservation::factory()->recycle($championship)->create(['bib' => 7, 'driver' => 'Reserved Holder']);

        Livewire::actingAs(User::factory()->organizer()->create())
            ->test(FreeRaceNumbers::class, ['championship' => $championship])
            ->set('checkNumber', '7')
            ->call('checkAvailability')
            ->assertSet('takenBy', 'Reserved Holder');
    }

    private function championship(bool $grantsReservation = true): Championship
    {
        return Championship::factory()->openForEnrollment(reservesRaceNumber: $grantsReservation)->create();
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Data\HolderData;
use App\Enums\EnrollmentPaymentStatus;
use App\Enums\EnrollmentStatus;
use App\Models\Championship;
use App\Models\Enrollment;
use App\Models\Licence;
use App\Models\User;
use Database\Factories\LicenceFactory;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\EnablesLicencesAndEnrollments;
use Tests\TestCase;

class EnrollmentCompetitorTest extends TestCase
{
    use EnablesLicencesAndEnrollments;
    use FastRefreshDatabase;

    public function test_an_underage_driver_needs_an_adult_competitor_other_than_themselves(): void
    {
        $championship = $this->championship();
        $user = User::factory()->create();
        $driver = Licence::factory()->for($user)->create(['holder' => $this->holder('Luca', 15), 'includes_competitor' => true]);
        $underage = Licence::factory()->for($user)->create(['holder' => $this->holder('Anna', 16), 'includes_competitor' => true]);
        $parent = Licence::factory()->for($user)->create(['holder' => $this->holder('Mario', 45), 'includes_competitor' => true]);

        $this->actingAs($user)
            ->get(route('public.championships.enrollment', $championship))
            ->assertSee('value="'.$parent->ulid.'"', false)
            ->assertDontSee('value="'.$underage->ulid.'"', false);

        foreach ([$driver, $underage] as $competitor) {
            $this->actingAs($user)
                ->post(route('public.championships.enrollment.store', $championship), ['subject' => 'licence:'.$driver->ulid, 'competitor_licence' => $competitor->ulid])
                ->assertSessionHasErrors('competitor_licence');
        }

        $this->actingAs($user)
            ->post(route('public.championships.enrollment.store', $championship), ['subject' => 'licence:'.$driver->ulid, 'competitor_licence' => $parent->ulid])
            ->assertSessionHasNoErrors();

        $this->assertTrue($championship->enrollments()->sole()->competitorLicence->is($parent));
    }

    public function test_the_driver_and_the_organizer_set_the_competitor_until_confirmation(): void
    {
        $championship = $this->championship();
        $user = User::factory()->create();
        $enrollment = Enrollment::factory()->for($championship)->for($user)->create([
            'licence_id' => Licence::factory()->verified()->for($user)->create(['holder' => $this->holder('Luca', 15)]),
        ]);
        $parent = Licence::factory()->for($user)->create(['holder' => $this->holder('Mario', 45), 'includes_competitor' => true]);
        $other = Licence::factory()->for($user)->create(['holder' => $this->holder('Paola', 40), 'includes_competitor' => true]);

        $this->assertContains(__('The driver is underage and no competitor is named.'), $enrollment->missingForConfirmation());

        $this->actingAs($user)
            ->put(route('my-enrollments.competitor.update', $enrollment), ['competitor_licence' => $parent->ulid])
            ->assertRedirect(route('my-enrollments.show', $enrollment));

        $this->assertTrue($enrollment->fresh()->competitorLicence->is($parent));
        $this->assertNotContains(__('The driver is underage and no competitor is named.'), $enrollment->fresh()->missingForConfirmation());

        $this->actingAs(User::factory()->organizer()->create())
            ->put(route('enrollments.competitor.update', $enrollment), ['competitor_licence' => $other->ulid])
            ->assertRedirect(route('enrollments.show', $enrollment));

        $this->assertTrue($enrollment->fresh()->competitorLicence->is($other));

        $enrollment->update(['status' => EnrollmentStatus::Confirmed]);

        $this->actingAs($user)
            ->put(route('my-enrollments.competitor.update', $enrollment), ['competitor_licence' => $parent->ulid])
            ->assertForbidden();
    }

    public function test_an_enrolled_declared_licence_cannot_be_changed_or_removed(): void
    {
        $championship = $this->championship();
        $user = User::factory()->create();
        $licence = Licence::factory()->for($user)->create();
        $competitor = Licence::factory()->for($user)->create(['holder' => $this->holder('Mario', 45), 'includes_competitor' => true]);
        Enrollment::factory()->for($championship)->for($user)->create(['licence_id' => $licence->getKey(), 'competitor_licence_id' => $competitor->getKey()]);

        foreach ([$licence, $competitor] as $enrolled) {
            $this->actingAs($user)->get(route('my-licences.edit', $enrolled))->assertForbidden();
            $this->actingAs($user)->delete(route('my-licences.destroy', $enrolled))->assertForbidden();
        }
    }

    public function test_an_enrollment_cannot_be_withdrawn_while_its_payment_proof_is_reviewed(): void
    {
        $championship = $this->championship();
        $user = User::factory()->create();
        $enrollment = Enrollment::factory()->for($championship)->for($user)->create(['amount' => 9000, 'payment_status' => EnrollmentPaymentStatus::PaymentSubmitted]);

        $this->actingAs($user)
            ->post(route('my-enrollments.withdraw', $enrollment))
            ->assertForbidden();

        $organizer = User::factory()->organizer()->create();

        $this->actingAs($organizer)
            ->post(route('enrollments.withdraw', $enrollment))
            ->assertSessionHasErrors('enrollment');

        $this->actingAs($organizer)
            ->post(route('enrollments.reject', $enrollment), ['reason' => 'Not eligible.'])
            ->assertSessionHasErrors('enrollment');

        $this->assertSame(EnrollmentStatus::Submitted, $enrollment->fresh()->status);
    }

    public function test_the_race_number_changes_only_while_enrollment_is_open(): void
    {
        $championship = $this->championship(grantsNumber: true);
        $enrollment = Enrollment::factory()->for($championship)->create();

        $this->actingAs($enrollment->user)
            ->get(route('my-enrollments.race-number.edit', $enrollment))
            ->assertOk();

        $this->travel(2)->months();

        $this->actingAs($enrollment->user)
            ->get(route('my-enrollments.race-number.edit', $enrollment))
            ->assertForbidden();
    }

    private function championship(bool $grantsNumber = false): Championship
    {
        return Championship::factory()
            ->openForEnrollment(reservesRaceNumber: $grantsNumber, requiresCompetitor: true)
            ->create(['start_at' => today()->startOfYear()->addMonths(2)]);
    }

    private function holder(string $firstName, int $age): HolderData
    {
        $holder = LicenceFactory::holder($firstName, 'Rossi');
        $holder->birth_date = today()->subYears($age)->toDateString();
        $holder->fiscal_code = mb_strtoupper($firstName).'RSS'.$age;

        return $holder;
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Features\LicencesAndEnrollments;
use App\Models\Championship;
use Carbon\CarbonImmutable;
use Laravel\Pennant\Feature;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\EnablesLicencesAndEnrollments;
use Tests\TestCase;

class PublicChampionshipEnrollmentTest extends TestCase
{
    use EnablesLicencesAndEnrollments;
    use FastRefreshDatabase;

    public function test_a_championship_without_enrollment_has_no_page(): void
    {
        $this->get(route('public.championships.enrollment', Championship::factory()->create()))->assertNotFound();
    }

    public function test_the_page_needs_early_access(): void
    {
        Feature::define(LicencesAndEnrollments::class, fn () => false);

        $this->get(route('public.championships.enrollment', $this->championship()))->assertNotFound();
    }

    public function test_enrollment_is_open_in_the_period(): void
    {
        $championship = $this->championship();

        $this->travelTo(CarbonImmutable::parse('2027-01-20 12:00', 'Europe/Rome'));

        $this->get(route('public.championships.enrollment', $championship))
            ->assertSuccessful()
            ->assertSee($championship->title)
            ->assertSee('Enrollment is open')
            ->assertSee('Free');
    }

    public function test_enrollment_is_closed_outside_the_period(): void
    {
        $championship = $this->championship();

        $this->travelTo(CarbonImmutable::parse('2027-01-09 12:00', 'Europe/Rome'));
        $this->get(route('public.championships.enrollment', $championship))->assertSee('Enrollment opens on');

        $this->travelTo(CarbonImmutable::parse('2027-03-01 12:00', 'Europe/Rome'));
        $this->get(route('public.championships.enrollment', $championship))->assertSee('Enrollment is closed');
    }

    public function test_a_paid_enrollment_shows_the_price(): void
    {
        $this->travelTo(CarbonImmutable::parse('2027-01-20 12:00', 'Europe/Rome'));

        $championship = $this->championship(amount: 18000);

        $this->get(route('public.championships.enrollment', $championship))
            ->assertSuccessful()
            ->assertSee('€180.00');
    }

    private function championship(int $amount = 0): Championship
    {
        config(['races.timezone' => 'Europe/Rome']);

        return Championship::factory()
            ->openForEnrollment(amount: $amount, opensAt: CarbonImmutable::parse('2027-01-10 09:00', 'Europe/Rome'), closesAt: CarbonImmutable::parse('2027-02-28 23:59', 'Europe/Rome'))
            ->create();
    }
}

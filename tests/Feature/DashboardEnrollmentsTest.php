<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Features\LicencesAndEnrollments;
use App\Models\Championship;
use App\Models\Enrollment;
use App\Models\User;
use Laravel\Pennant\Feature;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\EnablesLicencesAndEnrollments;
use Tests\TestCase;

class DashboardEnrollmentsTest extends TestCase
{
    use EnablesLicencesAndEnrollments;
    use FastRefreshDatabase;

    public function test_the_dashboard_proposes_the_championships_open_for_enrollment(): void
    {
        $open = $this->championship('Open championship', opensInDays: -1, closesInDays: 10);
        $future = $this->championship('Future championship', opensInDays: 5, closesInDays: 10);
        $closed = $this->championship('Closed championship', opensInDays: -10, closesInDays: -1);
        $disabled = $this->championship('Disabled championship', opensInDays: -1, closesInDays: 10, enabled: false);

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Open for enrollment')
            ->assertSee('Open championship')
            ->assertSee(route('public.championships.enrollment', $open))
            ->assertDontSee(route('public.championships.enrollment', $future))
            ->assertDontSee(route('public.championships.enrollment', $closed))
            ->assertDontSee(route('public.championships.enrollment', $disabled));
    }

    public function test_the_dashboard_lists_the_enrollments_of_the_user(): void
    {
        $user = User::factory()->create();
        $closed = $this->championship('Closed championship', opensInDays: -10, closesInDays: -1);
        $pending = Enrollment::factory()->for($closed)->for($user)->create();
        $confirmed = $this->championship('Confirmed championship', -10, -1);
        Enrollment::factory()->for($confirmed)->for($user)->confirmed()->create();
        $someoneElse = $this->championship('Someone else championship', -10, -1);
        Enrollment::factory()->for($someoneElse)->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Your enrollments')
            ->assertSee('Closed championship')
            ->assertSee(route('my-enrollments.show', $pending))
            ->assertSee(route('my-enrollments.show', $confirmed->enrollments()->sole()))
            ->assertDontSee(route('public.championships.enrollment', $someoneElse));
    }

    public function test_the_dashboard_has_no_enrollments_without_early_access(): void
    {
        Feature::define(LicencesAndEnrollments::class, fn () => false);
        Feature::flushCache();

        $this->championship('Open championship', opensInDays: -1, closesInDays: 10);

        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Open for enrollment');
    }

    private function championship(string $title, int $opensInDays, int $closesInDays, bool $enabled = true): Championship
    {
        $championship = Championship::factory()
            ->openForEnrollment(opensAt: now()->addDays($opensInDays), closesAt: now()->addDays($closesInDays))
            ->create(['title' => $title]);

        if (! $enabled) {
            $championship->enrollment->enabled = false;
            $championship->save();
        }

        return $championship;
    }
}

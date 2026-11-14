<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Features\LicencesAndEnrollments;
use App\Models\Championship;
use App\Models\EnrollmentOption;
use App\Models\User;
use Laravel\Pennant\Feature;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\TestCase;

class PublicLicencesAndEnrollmentsTest extends TestCase
{
    use FastRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['licences.enabled_types' => [], 'licences.renewal_price' => '50']);
    }

    public function test_guests_see_nothing_while_in_early_access(): void
    {
        config(['licences.public' => false]);

        $championship = $this->openChampionship();

        $this->get(route('welcome'))
            ->assertOk()
            ->assertDontSee('Enroll in a championship')
            ->assertDontSee(route('public.championships.enrollment', $championship))
            ->assertDontSee(route('public.licence-renewal'));

        $this->get(route('public.licence-renewal'))->assertNotFound();
        $this->get(route('public.championships.enrollment', $championship))->assertNotFound();

        $this->assertFalse(Feature::for(User::factory()->create())->active(LicencesAndEnrollments::class));
    }

    public function test_a_public_instance_proposes_enrollment_and_renewal_to_everyone(): void
    {
        config(['licences.public' => true]);

        $championship = $this->openChampionship();
        $closed = Championship::factory()->openForEnrollment(opensAt: now()->subDays(10), closesAt: now()->subDay())->create(['title' => 'Closed championship']);

        $this->get(route('welcome'))
            ->assertOk()
            ->assertSee('Enroll in a championship')
            ->assertSee('Open championship')
            ->assertSee(route('public.championships.enrollment', $championship))
            ->assertSee('From €600.00')
            ->assertDontSee('Closed championship')
            ->assertSee(route('public.licence-renewal'))
            ->assertSee('€50.00');

        $this->get(route('public.championships.enrollment', $championship))
            ->assertOk()
            ->assertSee('Sign in to enroll');

        $this->assertTrue(Feature::for(User::factory()->create())->active(LicencesAndEnrollments::class));
        $this->assertFalse($closed->enrollment->isOpen());
    }

    public function test_the_renewal_page_explains_the_types_price_and_documents(): void
    {
        config(['licences.public' => true, 'licences.enabled_types' => ['asi.lkm']]);

        $this->get(route('public.licence-renewal'))
            ->assertOk()
            ->assertSee('LKM')
            ->assertDontSee('NAZ E')
            ->assertSee('€50.00')
            ->assertSee('Medical certificate')
            ->assertSee('Parental authorisation')
            ->assertDontSee('Delegation')
            ->assertSee(route('login'));

        $this->actingAs(User::factory()->create())
            ->get(route('public.licence-renewal'))
            ->assertOk()
            ->assertSee(route('my-licences.index'));
    }

    private function openChampionship(): Championship
    {
        $championship = Championship::factory()
            ->openForEnrollment(amount: 95000, opensAt: now()->subDay(), closesAt: now()->addDays(10))
            ->create(['title' => 'Open championship']);

        EnrollmentOption::factory()->for($championship)->create(['amount' => 60000]);

        return $championship;
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Data\EnrollmentSettingsData;
use App\Features\LicencesAndProducts;
use App\Models\Championship;
use App\Models\Price;
use App\Models\Product;
use Carbon\CarbonImmutable;
use Laravel\Pennant\Feature;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\EnablesLicencesAndProducts;
use Tests\TestCase;

class PublicChampionshipEnrollmentTest extends TestCase
{
    use EnablesLicencesAndProducts;
    use FastRefreshDatabase;

    public function test_a_championship_without_enrollment_has_no_page(): void
    {
        $this->get(route('public.championships.enrollment', Championship::factory()->create()))->assertNotFound();
    }

    public function test_the_page_needs_early_access(): void
    {
        Feature::define(LicencesAndProducts::class, fn () => false);

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

        $product = Product::factory()->enrollment()->create();
        $price = Price::factory()->for($product)->create();
        $championship = $this->championship($product->championship, $product);

        $this->get(route('public.championships.enrollment', $championship))
            ->assertSuccessful()
            ->assertSee($price->label());
    }

    private function championship(?Championship $championship = null, ?Product $product = null): Championship
    {
        config(['races.timezone' => 'Europe/Rome']);

        $championship ??= Championship::factory()->create();
        $championship->enrollment = new EnrollmentSettingsData(
            enabled: true,
            opens_at: CarbonImmutable::parse('2027-01-10 09:00', 'Europe/Rome'),
            closes_at: CarbonImmutable::parse('2027-02-28 23:59', 'Europe/Rome'),
            product_id: $product?->getKey(),
        );
        $championship->save();

        return $championship;
    }
}

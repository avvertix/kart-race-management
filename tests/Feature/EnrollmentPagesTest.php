<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Data\EnrollmentSettingsData;
use App\Enums\OrderStatus;
use App\Models\Championship;
use App\Models\Enrollment;
use App\Models\Licence;
use App\Models\Price;
use App\Models\Product;
use App\Models\User;
use App\Notifications\OrderPriceSet;
use Database\Factories\LicenceFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\EnablesLicencesAndProducts;
use Tests\TestCase;

class EnrollmentPagesTest extends TestCase
{
    use EnablesLicencesAndProducts;
    use FastRefreshDatabase;

    public function test_the_driver_pays_the_enrollment_from_its_page(): void
    {
        Storage::fake('payments');
        $championship = $this->championship(withFee: true);
        $user = User::factory()->create();
        $licence = Licence::factory()->for($user)->create();

        $response = $this->actingAs($user)
            ->post(route('public.championships.enrollment.store', $championship), ['subject' => 'licence:'.$licence->ulid]);

        $enrollment = $championship->enrollments()->sole();

        $response->assertRedirect(route('my-enrollments.show', $enrollment));

        $this->actingAs($user)
            ->get(route('my-enrollments.show', $enrollment))
            ->assertOk()
            ->assertSee($championship->title)
            ->assertSee($licence->number)
            ->assertSee('€180.00')
            ->assertSee($enrollment->order->ulid)
            ->assertSee(route('my-enrollments.payment.store', $enrollment))
            ->assertDontSee(route('my-orders.show', $enrollment->order));

        $this->actingAs($user)
            ->post(route('my-enrollments.payment.store', $enrollment), [
                'proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('my-enrollments.show', $enrollment))
            ->assertSessionHasNoErrors();

        $this->assertSame(OrderStatus::PaymentSubmitted, $enrollment->order->fresh()->status);
    }

    public function test_a_notification_about_the_enrollment_payment_opens_the_enrollment_page(): void
    {
        $championship = $this->championship(withFee: true);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('public.championships.enrollment.store', $championship), ['subject' => 'licence:'.Licence::factory()->for($user)->create()->ulid]);

        $enrollment = $championship->enrollments()->sole();
        $user->notify(new OrderPriceSet($enrollment->order));

        $this->actingAs($user)
            ->get(route('notifications.open', $user->notifications()->sole()->getKey()))
            ->assertRedirect(route('my-enrollments.show', $enrollment));
    }

    public function test_only_the_driver_sees_the_enrollment_page(): void
    {
        $enrollment = Enrollment::factory()->for($this->championship())->create();

        $this->actingAs(User::factory()->create())
            ->get(route('my-enrollments.show', $enrollment))
            ->assertForbidden();

        $this->actingAs($enrollment->user)
            ->get(route('my-enrollments.show', $enrollment))
            ->assertOk();
    }

    public function test_the_championship_page_lists_the_enrollments_and_offers_only_licences_not_enrolled(): void
    {
        $championship = $this->championship();
        $user = User::factory()->create();
        $enrolled = Licence::factory()->for($user)->create(['number' => 'A100001']);
        $other = Licence::factory()->for($user)->create(['number' => 'A100002', 'holder' => LicenceFactory::holder('Luca', 'Bianchi')]);
        $enrollment = Enrollment::factory()->for($championship)->for($user)->create(['licence_id' => $enrolled->getKey()]);

        $this->actingAs($user)
            ->get(route('public.championships.enrollment', $championship))
            ->assertOk()
            ->assertSee(route('my-enrollments.show', $enrollment))
            ->assertDontSee('value="licence:'.$enrolled->ulid.'"', false)
            ->assertSee('value="licence:'.$other->ulid.'"', false)
            ->assertSee('Submit enrollment');
    }

    public function test_withdrawing_and_changing_the_number_lead_back_to_the_enrollment_page(): void
    {
        $championship = $this->championship();
        $enrollment = Enrollment::factory()->for($championship)->create();

        $this->actingAs($enrollment->user)
            ->post(route('my-enrollments.withdraw', $enrollment))
            ->assertRedirect(route('my-enrollments.show', $enrollment));
    }

    private function championship(bool $withFee = false): Championship
    {
        $championship = Championship::factory()->create();
        $fee = null;

        if ($withFee) {
            $fee = Product::factory()->enrollment()->recycle($championship)->create();
            Price::factory()->for($fee)->create(['amount' => 18000]);
        }

        $championship->enrollment = new EnrollmentSettingsData(
            enabled: true,
            opens_at: now()->subDay(),
            closes_at: now()->addMonth(),
            product_id: $fee?->getKey(),
            requires_competitor_when_underage: false,
        );
        $championship->save();

        return $championship;
    }
}

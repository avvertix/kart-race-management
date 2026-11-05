<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Orders\PlaceOrder;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Price;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Tests\EnablesLicencesAndProducts;
use Tests\TestCase;

class PublicProductControllerTest extends TestCase
{
    use EnablesLicencesAndProducts;
    use FastRefreshDatabase;

    public function test_active_products_are_listed_with_their_current_price(): void
    {
        Price::factory()->for(Product::factory()->state(['title' => 'Season package']))->create(['amount' => 18000]);
        Price::factory()->range(23000, 31000)->for(Product::factory()->state(['title' => 'Team package']))->create();
        Price::factory()->for(Product::factory()->inactive()->state(['title' => 'Old package']))->create();

        $this->get(route('public.products.index'))
            ->assertSuccessful()
            ->assertSee('Season package')
            ->assertSee('€180.00')
            ->assertSee('Team package')
            ->assertSee('from €230.00 to €310.00')
            ->assertSee('final price after review')
            ->assertDontSee('Old package');
    }

    public function test_guest_sees_the_product_and_why_an_account_is_needed(): void
    {
        $price = Price::factory()->for(Product::factory()->state(['title' => 'Season package']))->create();

        $this->get(route('public.products.show', $price->product))
            ->assertSuccessful()
            ->assertSee('Season package')
            ->assertSee('€180.00')
            ->assertSee('Why an account is needed')
            ->assertSee(route('public.products.continue', $price->product))
            ->assertSee(route('public.products.register', $price->product))
            ->assertDontSee(route('public.products.order', $price->product));
    }

    public function test_verified_user_sees_the_order_button(): void
    {
        $price = Price::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('public.products.show', $price->product))
            ->assertSuccessful()
            ->assertSee(route('public.products.order', $price->product))
            ->assertDontSee('Why an account is needed');
    }

    public function test_inactive_or_out_of_period_products_are_unavailable(): void
    {
        $inactive = Price::factory()->for(Product::factory()->inactive())->create()->product;
        $expired = Price::factory()->create(['starts_at' => now()->subMonth(), 'ends_at' => now()->subDay()])->product;

        foreach ([$inactive, $expired] as $product) {
            $this->actingAs(User::factory()->create())
                ->get(route('public.products.show', $product))
                ->assertSuccessful()
                ->assertSee('Not available at the moment')
                ->assertDontSee(route('public.products.order', $product));
        }
    }

    public function test_guest_signs_in_and_lands_back_on_the_product(): void
    {
        $product = Price::factory()->create()->product;
        $user = User::factory()->create();

        $this->get(route('public.products.continue', $product))->assertRedirect(route('login'));

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('public.products.continue', $product));

        $this->get(route('public.products.continue', $product))
            ->assertRedirect(route('public.products.show', $product));
    }

    public function test_guest_signs_up_verifies_the_email_and_lands_back_on_the_product(): void
    {
        $product = Price::factory()->create()->product;

        $this->get(route('public.products.register', $product))->assertRedirect(route('register'));

        $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => '!password1',
            'password_confirmation' => '!password1',
        ])->assertRedirect(route('public.products.continue', $product));

        $this->get(route('public.products.continue', $product))
            ->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'test@example.com')->sole();

        $verificationUrl = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->getKey(),
            'hash' => sha1($user->email),
        ]);

        $this->get($verificationUrl)->assertRedirect(route('public.products.continue', $product));

        $this->get(route('public.products.continue', $product))
            ->assertRedirect(route('public.products.show', $product));
    }

    public function test_guest_and_unverified_user_cannot_order(): void
    {
        $product = Price::factory()->create()->product;

        $this->post(route('public.products.order', $product))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->unverified()->create())
            ->post(route('public.products.order', $product))
            ->assertRedirect(route('verification.notice'));

        $this->assertDatabaseEmpty('orders');
    }

    public function test_verified_user_orders_and_uploads_the_payment_proof(): void
    {
        Storage::fake('payments');

        $user = User::factory()->create();
        $product = Price::factory()->for(Product::factory())->create(['amount' => 18000])->product;

        $response = $this->actingAs($user)->post(route('public.products.order', $product));

        $order = Order::sole();

        $response->assertRedirect(route('my-orders.show', $order));

        $this->actingAs($user)
            ->get(route('my-orders.show', $order))
            ->assertSuccessful()
            ->assertSee('€180.00');

        $this->actingAs($user)
            ->post(route('my-orders.payment.store', $order), [
                'proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('my-orders.show', $order))
            ->assertSessionHasNoErrors();

        $this->assertEquals(OrderStatus::PaymentSubmitted, $order->fresh()->status);
    }

    public function test_user_cannot_see_or_pay_the_order_of_another_user(): void
    {
        $order = (new PlaceOrder)(User::factory()->create(), Price::factory()->create()->product);

        $other = User::factory()->create();

        $this->actingAs($other)->get(route('my-orders.show', $order))->assertForbidden();

        $this->actingAs($other)
            ->post(route('my-orders.payment.store', $order), [
                'proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
            ])
            ->assertForbidden();
    }
}

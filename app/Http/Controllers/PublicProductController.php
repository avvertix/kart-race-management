<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Orders\PlaceOrder;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class PublicProductController extends Controller
{
    /**
     * The active products with their current price.
     */
    public function index(): View
    {
        return view('public-product.index', [
            'products' => Product::query()
                ->active()
                ->with('prices')
                ->orderBy('title')
                ->get(),
        ]);
    }

    /**
     * The product page, open to everyone. Inactive and out-of-period products show as unavailable.
     */
    public function show(Request $request, Product $product): View
    {
        $product->load(['prices', 'championship', 'grants.championship']);

        return view('public-product.show', [
            'product' => $product,
            'currentPrice' => $product->orderablePrice(),
            'user' => $request->user(),
        ]);
    }

    /**
     * Back to the product page after signing in and verifying the email.
     */
    public function continue(Product $product): RedirectResponse
    {
        return redirect()->route('public.products.show', $product);
    }

    /**
     * Create an account, then come back to the product page once the email is verified.
     */
    public function register(Product $product): RedirectResponse
    {
        abort_unless(Route::has('register'), 404);

        redirect()->setIntendedUrl(route('public.products.continue', $product));

        return redirect()->route('register');
    }

    /**
     * Place the order for the product. A Package including an Enrollment and no licence renewal is ordered from the
     * championship's enrollment page instead.
     */
    public function order(Request $request, Product $product, PlaceOrder $placeOrder): RedirectResponse
    {
        $enrollmentChampionship = $product->enrollmentChampionship();

        if ($enrollmentChampionship !== null) {
            return to_route('public.championships.enrollment', $enrollmentChampionship)
                ->with('flash.banner', __('Choose the package when enrolling, together with the licence to enroll.'));
        }

        $order = $placeOrder($request->user(), $product);

        return redirect()->route('my-orders.show', $order);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Orders\PlaceOrder;
use App\Enums\LicenceRole;
use App\Models\Licence;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
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
                ->soldOnTheirOwn()
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
            'licences' => $request->user() !== null && $product->needsLicenceWhenOrdered() ? $this->licencesFor($request->user(), $product)->get() : collect(),
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
     * Place the order for the product, for the chosen Licence when its race fee discounts need one. An enrollment fee, or
     * a Package including an Enrollment and no licence renewal, is ordered from the championship's enrollment page instead.
     */
    public function order(Request $request, Product $product, PlaceOrder $placeOrder): RedirectResponse
    {
        $this->authorize('create', Order::class);

        $product->load(['championship', 'grants.championship']);

        $enrollmentChampionship = $product->enrollmentChampionship();

        if ($enrollmentChampionship !== null) {
            if (! $enrollmentChampionship->enrollment->enabled) {
                throw ValidationException::withMessages(['product' => __('The product is not available at the moment.')]);
            }

            return to_route('public.championships.enrollment', $enrollmentChampionship)
                ->with('flash.banner', __('Choose it when enrolling, together with the licence to enroll.'));
        }

        $licence = null;

        if ($product->needsLicenceWhenOrdered()) {
            $request->validate(['licence' => ['required', 'string']]);

            $licence = $this->licencesFor($request->user(), $product)->where('ulid', $request->input('licence'))->first();

            if ($licence === null) {
                throw ValidationException::withMessages(['licence' => __('Choose one of your licences valid in the championship year.')]);
            }
        }

        $order = $placeOrder($request->user(), $product, $licence);

        return redirect()->route('my-orders.show', $order);
    }

    /**
     * The driver Licences of the User the Product's race fee discounts can be for: valid in their championships' year.
     *
     * @return Builder<Licence>
     */
    private function licencesFor(User $user, Product $product): Builder
    {
        return Licence::query()
            ->managedBy($user)
            ->where('role', LicenceRole::Driver)
            ->whereIn('validity_year', $product->entitlementYears())
            ->orderBy('id');
    }
}

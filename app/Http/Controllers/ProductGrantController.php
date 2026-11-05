<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\EntitlementMode;
use App\Enums\ProductGrantKind;
use App\Enums\ProductKind;
use App\Models\Championship;
use App\Models\Product;
use App\Models\ProductGrant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The organizer adds to a Package what it grants once paid, and removes it.
 */
class ProductGrantController extends Controller
{
    /**
     * Add a grant to a Package: a licence renewal, an Enrollment in a championship, or an Entitlement to its race fees.
     */
    public function store(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        abort_unless($product->kind === ProductKind::Package, 404);

        $validated = $request->validate([
            'kind' => ['required', Rule::enum(ProductGrantKind::class)],
            'championship' => ['nullable', 'required_unless:kind,'.ProductGrantKind::LicenceEmission->value, 'string', Rule::exists(Championship::class, 'uuid')],
            'mode' => ['nullable', 'required_if:kind,'.ProductGrantKind::Entitlement->value, Rule::enum(EntitlementMode::class)],
            'amount' => ['nullable', 'required_if:kind,'.ProductGrantKind::Entitlement->value, 'numeric', 'min:1', 'max:100000', Rule::when($request->input('mode') === EntitlementMode::Credit->value, 'integer')],
            'grants_bib_reservation' => ['nullable', 'boolean'],
        ]);

        $kind = ProductGrantKind::from($validated['kind']);
        $mode = $kind === ProductGrantKind::Entitlement ? EntitlementMode::from($validated['mode']) : null;

        $product->grants()->create([
            'kind' => $kind,
            'championship_id' => $kind === ProductGrantKind::LicenceEmission ? null : Championship::query()->where('uuid', $validated['championship'])->value('id'),
            'mode' => $mode,
            'amount' => $mode?->amountFromInput($validated['amount']),
            'grants_bib_reservation' => $kind === ProductGrantKind::Enrollment && $request->boolean('grants_bib_reservation'),
        ]);

        return to_route('products.edit', $product)
            ->with('flash.banner', __('Grant added to :product.', ['product' => $product->title]));
    }

    /**
     * Remove a grant. Orders already paid keep what they were granted.
     */
    public function destroy(ProductGrant $productGrant): RedirectResponse
    {
        $product = $productGrant->product;

        $this->authorize('update', $product);

        $productGrant->delete();

        return to_route('products.edit', $product)
            ->with('flash.banner', __('Grant removed from :product.', ['product' => $product->title]));
    }
}

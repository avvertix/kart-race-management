<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ProductKind;
use App\Models\Championship;
use App\Models\Currency;
use App\Models\Product;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Product::class, 'product');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('product.index', [
            'products' => Product::query()
                ->with(['prices', 'championship'])
                ->orderBy('title')
                ->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('product.create', $this->formOptions());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'kind' => ['required', Rule::enum(ProductKind::class)],
        ]);

        $kind = $request->enum('kind', ProductKind::class);

        $validated = $this->validate($request, $this->rules($kind));

        $product = DB::transaction(function () use ($validated, $request, $kind) {
            $product = Product::create([
                ...$this->attributes($validated, $request),
                'kind' => $kind,
            ]);

            $this->syncPrices($product, $validated['prices'] ?? []);

            return $product;
        });

        return redirect()->route('products.index')
            ->with('flash.banner', __(':product created.', [
                'product' => $product->title,
            ]));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product): View
    {
        $product->load(['prices' => fn ($query) => $query->withCount('orderLines')]);

        return view('product.edit', [
            ...$this->formOptions(),
            'product' => $product,
        ]);
    }

    /**
     * Update the specified resource in storage. The kind cannot change.
     */
    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $this->validate($request, $this->rules($product->kind, $product));

        $lockedPriceIds = $product->prices()->has('orderLines')->pluck('id');

        /** @var array<int, array<string, mixed>> $submittedPrices */
        $submittedPrices = $validated['prices'] ?? [];

        $missingLockedPrices = $lockedPriceIds->diff(collect($submittedPrices)->pluck('id')->filter());

        if ($missingLockedPrices->isNotEmpty()) {
            throw ValidationException::withMessages([
                'prices' => __('Prices already used by orders cannot be removed. Set an end date instead.'),
            ]);
        }

        DB::transaction(function () use ($validated, $request, $product, $lockedPriceIds) {
            $product->update($this->attributes($validated, $request));

            $this->syncPrices($product, $validated['prices'] ?? [], $lockedPriceIds->all());
        });

        return redirect()->route('products.index')
            ->with('flash.banner', __(':product updated.', [
                'product' => $product->title,
            ]));
    }

    /**
     * The championships a product can be linked to.
     *
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'championships' => Championship::query()->orderByDesc('start_at')->get(),
        ];
    }

    /**
     * The validation rules of a Product and its Prices. Amounts are in euro, dates are days in the races timezone.
     *
     * @return array<string, mixed>
     */
    private function rules(ProductKind $kind, ?Product $product = null): array
    {
        return [
            'title' => 'required|string|max:250',
            'description' => 'nullable|string|max:1000',
            'active' => 'nullable|boolean',
            'championship' => match ($kind) {
                ProductKind::Enrollment => ['required', 'string', Rule::exists('championships', 'uuid')],
                ProductKind::Package => ['nullable', 'string', Rule::exists('championships', 'uuid')],
            },

            'prices' => 'nullable|array',
            'prices.*.id' => ['nullable', 'integer', 'distinct', Rule::exists('prices', 'id')->where('product_id', $product?->getKey() ?? 0)],
            'prices.*.type' => ['required', Rule::in(['fixed', 'range'])],
            'prices.*.amount' => ['exclude_unless:prices.*.type,fixed', 'required', 'numeric', 'decimal:0,2', 'min:0'],
            'prices.*.amount_min' => ['exclude_unless:prices.*.type,range', 'required', 'numeric', 'decimal:0,2', 'min:0'],
            'prices.*.amount_max' => ['exclude_unless:prices.*.type,range', 'required', 'numeric', 'decimal:0,2', 'gt:prices.*.amount_min'],
            'prices.*.starts_on' => ['required', 'date_format:Y-m-d'],
            'prices.*.ends_on' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:prices.*.starts_on'],
        ];
    }

    /**
     * The attributes of the Product itself.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function attributes(array $validated, Request $request): array
    {
        return [
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'championship_id' => isset($validated['championship']) ? Championship::where('uuid', $validated['championship'])->value('id') : null,
            'active' => $request->boolean('active'),
        ];
    }

    /**
     * Make the prices match the submitted ones. Locked prices, already used by orders, only change their end date.
     *
     * @param  array<int, array<string, mixed>>  $prices
     * @param  array<int, int>  $lockedPriceIds
     */
    private function syncPrices(Product $product, array $prices, array $lockedPriceIds = []): void
    {
        $keptIds = collect($prices)->pluck('id')->filter()->all();

        $product->prices()->whereNotIn('id', $keptIds)->delete();

        foreach ($prices as $price) {
            $isFixed = $price['type'] === 'fixed';

            $attributes = [
                'amount' => $isFixed ? $this->toCents($price['amount']) : null,
                'amount_min' => $isFixed ? null : $this->toCents($price['amount_min']),
                'amount_max' => $isFixed ? null : $this->toCents($price['amount_max']),
                'currency' => Currency::EUR,
                'starts_at' => CarbonImmutable::parse($price['starts_on'], config('races.timezone'))->startOfDay()->setTimezone(config('app.timezone')),
                'ends_at' => isset($price['ends_on']) ? CarbonImmutable::parse($price['ends_on'], config('races.timezone'))->endOfDay()->setTimezone(config('app.timezone')) : null,
            ];

            if (! isset($price['id'])) {
                $product->prices()->create($attributes);

                continue;
            }

            $existing = $product->prices()->whereKey($price['id'])->firstOrFail();

            if (! in_array($price['id'], $lockedPriceIds)) {
                $existing->update($attributes);

                continue;
            }

            if ($attributes['ends_at']?->lessThan($existing->starts_at)) {
                throw ValidationException::withMessages([
                    'prices' => __('A price cannot end before it starts.'),
                ]);
            }

            $existing->update(['ends_at' => $attributes['ends_at']]);
        }
    }

    private function toCents(string|int|float $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }
}

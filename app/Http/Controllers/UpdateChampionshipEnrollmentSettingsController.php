<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\EnrollmentSettingsData;
use App\Enums\ProductKind;
use App\Models\Championship;
use App\Models\Price;
use App\Models\Product;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UpdateChampionshipEnrollmentSettingsController extends Controller
{
    /**
     * Show the enrollment settings of the championship.
     */
    public function edit(Championship $championship): View
    {
        $this->authorize('update', $championship);

        return view('championship.enrollment-settings', [
            'championship' => $championship,
            'products' => $this->enrollmentProducts($championship)->get(),
        ]);
    }

    /**
     * Save the enrollment settings. Turning enrollment on needs both dates; the Product must be an enrollment Product
     * of the championship with fixed prices only, and can cover the licence renewal.
     */
    public function update(Request $request, Championship $championship): RedirectResponse
    {
        $this->authorize('update', $championship);

        $validated = $this->validate($request, [
            'enabled' => ['nullable', 'boolean'],
            'opens_at' => ['nullable', 'required_if_accepted:enabled', 'date'],
            'closes_at' => ['nullable', 'required_if_accepted:enabled', 'date', Rule::when($request->filled('opens_at'), 'after:opens_at')],
            'product' => [
                'nullable',
                'string',
                Rule::exists(Product::class, 'ulid')
                    ->where('kind', ProductKind::Enrollment->value)
                    ->where('championship_id', $championship->getKey()),
                function (string $attribute, mixed $value, Closure $fail) {
                    if (Price::query()->whereHas('product', fn (Builder $query) => $query->where('ulid', $value))->whereNull('amount')->exists()) {
                        $fail(__('The enrollment fee must be a fixed amount.'));
                    }
                },
            ],
            'fee_covers_licence_renewal' => ['nullable', 'boolean'],
            'grants_bib_reservation' => ['nullable', 'boolean'],
            'requires_competitor_when_underage' => ['nullable', 'boolean'],
        ]);

        $championship->enrollment = new EnrollmentSettingsData(
            enabled: $request->boolean('enabled'),
            opens_at: $this->parseInRacesTimezone($validated['opens_at'] ?? null),
            closes_at: $this->parseInRacesTimezone($validated['closes_at'] ?? null),
            product_id: isset($validated['product']) ? $this->enrollmentProducts($championship)->where('ulid', $validated['product'])->value('id') : null,
            grants_bib_reservation: $request->boolean('grants_bib_reservation'),
            requires_competitor_when_underage: $request->boolean('requires_competitor_when_underage'),
            fee_covers_licence_renewal: isset($validated['product']) && $request->boolean('fee_covers_licence_renewal'),
        );

        $championship->save();

        return to_route('championships.show', $championship)
            ->with('flash.banner', __(':championship enrollment settings updated.', [
                'championship' => $championship->title,
            ]));
    }

    /**
     * @return Builder<Product>
     */
    private function enrollmentProducts(Championship $championship): Builder
    {
        return Product::query()
            ->where('kind', ProductKind::Enrollment->value)
            ->where('championship_id', $championship->getKey())
            ->orderBy('title');
    }

    /**
     * A date and time entered by the organizer, read in the races' timezone and stored in the application one.
     */
    private function parseInRacesTimezone(?string $value): ?CarbonImmutable
    {
        return $value === null
            ? null
            : CarbonImmutable::parse($value, config('races.timezone'))->setTimezone(config('app.timezone'));
    }
}

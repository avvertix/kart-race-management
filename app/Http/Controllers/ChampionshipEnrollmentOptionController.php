<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\EntitlementMode;
use App\Models\Championship;
use App\Models\DriverLicence;
use App\Models\EnrollmentOption;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The organizer adds and changes the options drivers enroll in a championship with.
 */
class ChampionshipEnrollmentOptionController extends Controller
{
    /**
     * The form to add an option.
     */
    public function create(Championship $championship): View
    {
        $this->authorize('update', $championship);

        return view('enrollment-option.form', [
            'championship' => $championship,
            'option' => new EnrollmentOption,
        ]);
    }

    /**
     * Add an option, offered to drivers.
     */
    public function store(Request $request, Championship $championship): RedirectResponse
    {
        $this->authorize('update', $championship);

        $option = new EnrollmentOption($this->validated($request, $championship, creating: true));
        $option->championship()->associate($championship);
        $option->save();

        return to_route('championships.enrollment-settings.edit', $championship)
            ->with('flash.banner', __('Enrollment option :option added.', ['option' => $option->title]));
    }

    /**
     * The form to change an option.
     */
    public function edit(EnrollmentOption $enrollmentOption): View
    {
        $this->authorize('update', $enrollmentOption->championship);

        return view('enrollment-option.form', [
            'championship' => $enrollmentOption->championship,
            'option' => $enrollmentOption,
        ]);
    }

    /**
     * Change an option, or stop offering it. Enrollments that chose it keep the amount they were asked. A race fee
     * discount set while the championship used Entitlements is kept when it no longer does, as the form hides it.
     */
    public function update(Request $request, EnrollmentOption $enrollmentOption): RedirectResponse
    {
        $championship = $enrollmentOption->championship;

        $this->authorize('update', $championship);

        $attributes = $this->validated($request, $championship, creating: false);

        if (! $championship->entitlements_enabled) {
            unset($attributes['entitlement_mode'], $attributes['entitlement_amount']);
        }

        $enrollmentOption->update($attributes);

        return to_route('championships.enrollment-settings.edit', $championship)
            ->with('flash.banner', __('Enrollment option :option updated.', ['option' => $enrollmentOption->title]));
    }

    /**
     * The option's attributes from the form. The amount and the deposit are entered in euro, the deposit below the amount
     * and with the balance due date, ignored without a deposit; race fee discounts need the championship
     * to use Entitlements. A new option is offered to drivers; an existing one only while "offered" stays ticked.
     *
     * @return array<string, mixed>
     */
    private function validated(Request $request, Championship $championship, bool $creating): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:100000'],
            'deposit_amount' => ['nullable', 'numeric', 'decimal:0,2', 'min:0.01', 'lt:amount'],
            'balance_due_on' => ['nullable', 'required_with:deposit_amount', 'date'],
            'covers_licence_renewal' => ['nullable', 'boolean'],
            'reserves_race_number' => ['nullable', 'boolean'],
            'entitlement_mode' => [
                'nullable',
                Rule::enum(EntitlementMode::class),
                Rule::prohibitedIf(! $championship->entitlements_enabled),
            ],
            'entitlement_amount' => [
                'nullable',
                'required_with:entitlement_mode',
                'numeric',
                'min:1',
                'max:100000',
                Rule::when($request->input('entitlement_mode') === EntitlementMode::Credit->value, 'integer'),
                Rule::when($request->input('entitlement_mode') === EntitlementMode::Balance->value, 'decimal:0,2'),
            ],
            'licence_categories' => ['nullable', 'array'],
            'licence_categories.*' => [Rule::enum(DriverLicence::class)],
            'active' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ]);

        $mode = isset($validated['entitlement_mode']) ? EntitlementMode::from($validated['entitlement_mode']) : null;

        return [
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'amount' => (int) round(((float) $validated['amount']) * 100),
            'deposit_amount' => isset($validated['deposit_amount']) ? (int) round(((float) $validated['deposit_amount']) * 100) : null,
            'balance_due_on' => isset($validated['deposit_amount']) ? $validated['balance_due_on'] : null,
            'covers_licence_renewal' => $request->boolean('covers_licence_renewal'),
            'reserves_race_number' => $request->boolean('reserves_race_number'),
            'entitlement_mode' => $mode,
            'entitlement_amount' => $mode?->amountFromInput($validated['entitlement_amount']),
            'licence_categories' => array_values(array_map('intval', $validated['licence_categories'] ?? [])),
            'active' => $creating || $request->boolean('active'),
            'sort' => (int) ($validated['sort'] ?? 0),
        ];
    }
}

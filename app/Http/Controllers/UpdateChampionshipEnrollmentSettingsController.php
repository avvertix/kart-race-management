<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\EnrollmentSettingsData;
use App\Models\Championship;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UpdateChampionshipEnrollmentSettingsController extends Controller
{
    /**
     * Show the enrollment settings of the championship, with its enrollment options.
     */
    public function edit(Championship $championship): View
    {
        $this->authorize('update', $championship);

        return view('championship.enrollment-settings', [
            'championship' => $championship,
            'options' => $championship->enrollmentOptions()->get(),
        ]);
    }

    /**
     * Save the enrollment settings. Turning enrollment on needs both dates and at least one option offered to drivers.
     */
    public function update(Request $request, Championship $championship): RedirectResponse
    {
        $this->authorize('update', $championship);

        $validated = $this->validate($request, [
            'enabled' => [
                'nullable',
                'boolean',
                function (string $attribute, mixed $value, Closure $fail) use ($championship) {
                    if (filter_var($value, FILTER_VALIDATE_BOOLEAN) && $championship->enrollmentOptions()->offered()->doesntExist()) {
                        $fail(__('Add an enrollment option before letting drivers enroll.'));
                    }
                },
            ],
            'opens_at' => ['nullable', 'required_if_accepted:enabled', 'date'],
            'closes_at' => ['nullable', 'required_if_accepted:enabled', 'date', Rule::when($request->filled('opens_at'), 'after:opens_at')],
            'requires_competitor_when_underage' => ['nullable', 'boolean'],
        ]);

        $championship->enrollment = new EnrollmentSettingsData(
            enabled: $request->boolean('enabled'),
            opens_at: $this->parseInRacesTimezone($validated['opens_at'] ?? null),
            closes_at: $this->parseInRacesTimezone($validated['closes_at'] ?? null),
            requires_competitor_when_underage: $request->boolean('requires_competitor_when_underage'),
        );

        $championship->save();

        return to_route('championships.show', $championship)
            ->with('flash.banner', __(':championship enrollment settings updated.', [
                'championship' => $championship->title,
            ]));
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

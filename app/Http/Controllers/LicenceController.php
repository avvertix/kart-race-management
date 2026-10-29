<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\LicenceProvider;
use App\Models\Licence;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LicenceController extends Controller
{
    /**
     * All licences, filtered by provider, validity year, and declared licences waiting for verification.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Licence::class);

        $filters = $request->validate([
            'provider' => ['nullable', Rule::enum(LicenceProvider::class)],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'unverified' => ['nullable', 'boolean'],
        ]);

        return view('licence.index', [
            'filters' => $filters,
            'years' => Licence::query()->distinct()->orderByDesc('validity_year')->pluck('validity_year'),
            'licences' => Licence::query()
                ->with('user')
                ->when($filters['provider'] ?? null, fn ($query, $provider) => $query->where('provider', $provider))
                ->when($filters['year'] ?? null, fn ($query, $year) => $query->where('validity_year', $year))
                ->when($request->boolean('unverified'), fn ($query) => $query->awaitingVerification())
                ->orderByDesc('validity_year')
                ->latest()
                ->paginate(50)
                ->withQueryString(),
        ]);
    }

    /**
     * The licence detail.
     */
    public function show(Licence $licence): View
    {
        $this->authorize('view', $licence);

        $licence->load('user');

        return view('licence.show', [
            'licence' => $licence,
        ]);
    }

    /**
     * Mark a declared licence as verified.
     */
    public function verify(Request $request, Licence $licence): RedirectResponse
    {
        $this->authorize('verify', $licence);

        $licence->update([
            'verified_at' => now(),
            'verified_by' => $request->user()->getKey(),
        ]);

        return redirect()->route('licences.show', $licence)
            ->with('flash.banner', __('Licence verified.'));
    }
}

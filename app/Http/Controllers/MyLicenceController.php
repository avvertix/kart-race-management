<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\TransferParticipationLicence;
use App\Data\HolderData;
use App\Enums\LicenceProvider;
use App\Enums\LicenceSource;
use App\Models\Licence;
use App\Models\LicenceRequest;
use App\Rules\LicenseNumberValidationRule;
use App\Support\LicenceCatalogue;
use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MyLicenceController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Licence::class, 'licence', ['except' => ['index']]);
    }

    /**
     * The licences managed by the signed-in user, their open requests, and which licences can be renewed.
     */
    public function index(Request $request): View
    {
        $this->authorize('create', Licence::class);

        $licenceRequests = LicenceRequest::query()
            ->requestedBy($request->user())
            ->open()
            ->latest()
            ->get();

        $licences = Licence::query()
            ->managedBy($request->user())
            ->orderByDesc('validity_year')
            ->orderBy('id')
            ->get();

        $renewalYear = LicenceRequest::validityYearFor(now());

        return view('my-licence.index', [
            'licenceRequests' => $licenceRequests,
            'licences' => $licences,
            'renewableLicenceIds' => $this->renewableLicenceIds($licences, $licenceRequests, $renewalYear),
            'renewalYear' => $renewalYear,
            'participations' => TransferParticipationLicence::candidates($request->user()),
        ]);
    }

    /**
     * Show the form for declaring a licence obtained elsewhere.
     */
    public function create(): View
    {
        return view('my-licence.create', $this->formOptions());
    }

    /**
     * Declare a licence obtained elsewhere.
     */
    public function store(Request $request): RedirectResponse
    {
        $attributes = $this->validatedAttributes($request);

        $licence = $this->refusingDuplicates($attributes, fn () => Licence::create([
            ...$attributes,
            'user_id' => $request->user()->getKey(),
            'source' => LicenceSource::Declared,
        ]));

        return redirect()->route('my-licences.show', $licence)
            ->with('flash.banner', __('Licence added. The organizer will verify it.'));
    }

    /**
     * The licence detail.
     */
    public function show(Licence $licence): View
    {
        return view('my-licence.show', [
            'licence' => $licence,
        ]);
    }

    /**
     * Show the form for changing a declared licence.
     */
    public function edit(Licence $licence): View
    {
        return view('my-licence.edit', [
            ...$this->formOptions($licence),
            'licence' => $licence,
        ]);
    }

    /**
     * Change a declared licence, before it is verified.
     */
    public function update(Request $request, Licence $licence): RedirectResponse
    {
        $attributes = $this->validatedAttributes($request, $licence);

        $this->refusingDuplicates($attributes, fn () => $licence->update($attributes));

        return redirect()->route('my-licences.show', $licence)
            ->with('flash.banner', __('Licence updated.'));
    }

    /**
     * Remove a declared licence, before it is verified.
     */
    public function destroy(Licence $licence): RedirectResponse
    {
        $licence->delete();

        return redirect()->route('my-licences.index')
            ->with('flash.banner', __('Licence removed.'));
    }

    /**
     * Download the temporary licence of an emitted licence. Reached through a signed link.
     */
    public function temporaryLicence(Licence $licence): BinaryFileResponse
    {
        $this->authorize('view', $licence);

        abort_if($licence->temporary_licence_path === null, 404);

        return response()->file(Storage::disk('licence-documents')->path($licence->temporary_licence_path));
    }

    /**
     * The latest licence of each holder, when older than the year renewals are for and not already being renewed.
     * Holders are matched by fiscal code, or by name and birth date without one.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, Licence>  $licences
     * @param  \Illuminate\Database\Eloquent\Collection<int, LicenceRequest>  $licenceRequests
     * @return array<int, int>
     */
    private function renewableLicenceIds(Collection $licences, Collection $licenceRequests, int $renewalYear): array
    {
        $renewingHashes = $licenceRequests->pluck('previous_licence_number_hash')->filter()->all();

        return $licences->toBase()
            ->groupBy(fn (Licence $licence) => $licence->holder->identityKey())
            ->map(fn (Collection $holderLicences) => $holderLicences->sortByDesc('validity_year')->first())
            ->filter()
            ->filter(fn (Licence $licence) => $licence->validity_year < $renewalYear && ! in_array($licence->number_hash, $renewingHashes, true))
            ->map(fn (Licence $licence) => (int) $licence->getKey())
            ->values()
            ->all();
    }

    /**
     * The Licence Types a licence can be declared for, by provider. A changed licence keeps its type even when no longer offered.
     *
     * @return array<string, mixed>
     */
    private function formOptions(?Licence $licence = null): array
    {
        return [
            'licenceTypes' => collect(LicenceCatalogue::providers())
                ->mapWithKeys(fn (LicenceProvider $provider) => [
                    $provider->value => LicenceCatalogue::selectableTypes($provider, $licence?->provider === $provider ? $licence->licence_type : null),
                ])
                ->all(),
        ];
    }

    /**
     * Validate the declared licence and derive the attributes stored with it.
     *
     * @return array<string, mixed>
     */
    private function validatedAttributes(Request $request, ?Licence $licence = null): array
    {
        $request->validate([
            'provider' => ['required', Rule::enum(LicenceProvider::class)->only(LicenceCatalogue::providers())],
        ]);

        $provider = $request->enum('provider', LicenceProvider::class);

        $currentLicenceType = $licence?->provider === $provider ? $licence->licence_type : null;

        $currentYear = now()->year;

        $validated = $request->validate([
            'licence_type' => ['required', 'string', Rule::in(LicenceCatalogue::selectableCodes($provider, $currentLicenceType))],
            'driver_only' => ['nullable', 'boolean'],
            'number' => ['required', 'string', 'max:250', new LicenseNumberValidationRule],
            'validity_year' => ['required', 'integer', Rule::in([$currentYear, $currentYear + 1])],
            'holder' => ['required', 'array'],
            ...HolderData::validationRules($request->all()),
        ]);

        $isDuplicate = Licence::query()
            ->where('number_hash', Licence::hashNumber($validated['number']))
            ->where('validity_year', $validated['validity_year'])
            ->where('provider', $provider)
            ->when($licence, fn ($query) => $query->whereKeyNot($licence->getKey()))
            ->exists();

        if ($isDuplicate) {
            $this->throwDuplicate((int) $validated['validity_year']);
        }

        $licenceType = LicenceCatalogue::find($provider, $validated['licence_type']);

        abort_if($licenceType === null, 404);

        return [
            ...$licenceType->licenceAttributes(includesCompetitor: ! $request->boolean('driver_only')),
            'number' => $validated['number'],
            'validity_year' => (int) $validated['validity_year'],
            'holder' => HolderData::fromValidated($validated['holder']),
        ];
    }

    /**
     * Run the save, turning a duplicate caught by the unique index, e.g. two declarations at the same time, into the validation error.
     *
     * @template TResult
     *
     * @param  array<string, mixed>  $attributes
     * @param  Closure(): TResult  $save
     * @return TResult
     */
    private function refusingDuplicates(array $attributes, Closure $save): mixed
    {
        try {
            return $save();
        } catch (UniqueConstraintViolationException) {
            $this->throwDuplicate($attributes['validity_year']);
        }
    }

    /**
     * @throws ValidationException
     */
    private function throwDuplicate(int $year): never
    {
        throw ValidationException::withMessages([
            'number' => __('This licence is already registered for :year. Contact the organizer.', ['year' => $year]),
        ]);
    }
}

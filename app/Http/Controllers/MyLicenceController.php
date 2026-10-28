<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\HolderData;
use App\Enums\LicenceLevel;
use App\Enums\LicenceProvider;
use App\Enums\LicenceSource;
use App\Models\Licence;
use App\Models\LicenceRequest;
use App\Models\LicenceType;
use App\Rules\LicenseNumberValidationRule;
use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
     * The licences managed by the signed-in user.
     */
    public function index(Request $request): View
    {
        $this->authorize('create', Licence::class);

        return view('my-licence.index', [
            'licenceRequests' => LicenceRequest::query()
                ->requestedBy($request->user())
                ->open()
                ->with(['product', 'order'])
                ->latest()
                ->get(),
            'licences' => Licence::query()
                ->managedBy($request->user())
                ->with(['licenceType', 'grade'])
                ->orderByDesc('validity_year')
                ->orderBy('id')
                ->get(),
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
        $licence->load(['licenceType', 'grade']);

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
     * The licence types a licence can be declared for, with their grades. A changed licence keeps its type even when deactivated.
     *
     * @return array<string, mixed>
     */
    private function formOptions(?Licence $licence = null): array
    {
        return [
            'licenceTypes' => LicenceType::query()
                ->offeredFor($licence)
                ->with('grades')
                ->orderBy('provider')
                ->orderBy('name')
                ->get(),
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
            'licence_type' => ['required', 'string'],
        ]);

        $licenceType = LicenceType::query()->offeredFor($licence)->where('ulid', $request->input('licence_type'))->first();

        if ($licenceType === null) {
            throw ValidationException::withMessages([
                'licence_type' => __('validation.exists', ['attribute' => __('Licence type')]),
            ]);
        }

        $isAci = $licenceType->provider === LicenceProvider::Aci;

        $currentYear = now()->year;

        $validated = $request->validate([
            'level' => $isAci ? ['required', Rule::enum(LicenceLevel::class)] : ['prohibited'],
            'licence_grade' => [
                'nullable',
                'integer',
                Rule::exists('licence_grades', 'id')
                    ->where('licence_type_id', $licenceType->getKey())
                    ->when($isAci, fn ($rule) => $rule->where('level', $request->input('level'))),
            ],
            'grade_other' => ['nullable', 'required_without:licence_grade', 'string', 'max:250'],
            'number' => ['required', 'string', 'max:250', new LicenseNumberValidationRule],
            'validity_year' => ['required', 'integer', Rule::in([$currentYear, $currentYear + 1])],
            'holder' => ['required', 'array'],
            ...HolderData::validationRules($request->all()),
        ]);

        $isDuplicate = Licence::query()
            ->where('number_hash', Licence::hashNumber($validated['number']))
            ->where('validity_year', $validated['validity_year'])
            ->where('provider', $licenceType->provider)
            ->when($licence, fn ($query) => $query->whereKeyNot($licence->getKey()))
            ->exists();

        if ($isDuplicate) {
            $this->throwDuplicate((int) $validated['validity_year']);
        }

        $level = $isAci ? LicenceLevel::from($validated['level']) : null;

        return [
            'provider' => $licenceType->provider,
            'licence_type_id' => $licenceType->getKey(),
            'role' => $licenceType->role,
            'level' => $level,
            'licence_grade_id' => $validated['licence_grade'] ?? null,
            'grade_other' => filled($validated['licence_grade'] ?? null) ? null : $validated['grade_other'],
            'race_licence_category' => $licenceType->raceLicenceCategory($level),
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

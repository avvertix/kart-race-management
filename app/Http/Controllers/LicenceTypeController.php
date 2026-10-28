<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\LicenceDocumentKind;
use App\Enums\LicenceDocumentScope;
use App\Enums\LicenceFormTemplate;
use App\Enums\LicenceLevel;
use App\Enums\LicenceProvider;
use App\Enums\LicenceRole;
use App\Models\DriverLicence;
use App\Models\LicenceType;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LicenceTypeController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(LicenceType::class, 'licence_type');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('licence-type.index', [
            'licenceTypes' => LicenceType::query()
                ->withCount(['grades', 'documents'])
                ->orderBy('provider')
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('licence-type.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'provider' => ['required', Rule::enum(LicenceProvider::class)],
            'role' => ['required', Rule::enum(LicenceRole::class)],
        ]);

        $provider = $request->enum('provider', LicenceProvider::class);
        $role = $request->enum('role', LicenceRole::class);

        $validated = $this->validate($request, $this->rules($provider, $role));

        $licenceType = DB::transaction(function () use ($validated, $request, $provider, $role) {
            $licenceType = LicenceType::create([
                ...$this->attributes($validated, $request),
                'provider' => $provider,
                'role' => $role,
            ]);

            $this->syncRows($licenceType->grades(), $this->gradeRows($validated['grades'] ?? []));
            $this->syncRows($licenceType->documents(), $this->documentRows($validated['documents'] ?? []));

            return $licenceType;
        });

        return redirect()->route('licence-types.index')
            ->with('flash.banner', __(':licence created.', [
                'licence' => $licenceType->name,
            ]));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(LicenceType $licenceType): View
    {
        $licenceType->load(['grades', 'documents']);

        return view('licence-type.edit', [
            'licenceType' => $licenceType,
        ]);
    }

    /**
     * Update the specified resource in storage. Provider and role cannot change.
     */
    public function update(Request $request, LicenceType $licenceType): RedirectResponse
    {
        $validated = $this->validate($request, $this->rules($licenceType->provider, $licenceType->role, $licenceType));

        DB::transaction(function () use ($validated, $request, $licenceType) {
            $licenceType->update($this->attributes($validated, $request));

            $this->syncRows($licenceType->grades(), $this->gradeRows($validated['grades'] ?? []));
            $this->syncRows($licenceType->documents(), $this->documentRows($validated['documents'] ?? []));
        });

        return redirect()->route('licence-types.index')
            ->with('flash.banner', __(':licence updated.', [
                'licence' => $licenceType->name,
            ]));
    }

    /**
     * The validation rules of a Licence Type, its grades and documents.
     *
     * @return array<string, mixed>
     */
    private function rules(LicenceProvider $provider, LicenceRole $role, ?LicenceType $licenceType = null): array
    {
        $isAci = $provider === LicenceProvider::Aci;

        $needsRaceLicenceCategory = $provider === LicenceProvider::Asi && $role === LicenceRole::Driver;

        return [
            'name' => 'required|string|max:250',
            'description' => 'nullable|string|max:1000',
            'active' => 'nullable|boolean',
            'race_licence_category' => $needsRaceLicenceCategory
                ? ['required', 'integer', Rule::in([DriverLicence::LOCAL_NATIONAL->value, DriverLicence::LOCAL_INTERNATIONAL->value])]
                : ['prohibited'],

            'grades' => 'nullable|array',
            'grades.*.id' => ['nullable', 'integer', 'distinct', Rule::exists('licence_grades', 'id')->where('licence_type_id', $licenceType?->getKey() ?? 0)],
            'grades.*.level' => $isAci ? ['required', Rule::enum(LicenceLevel::class)] : ['prohibited'],
            'grades.*.name' => 'required|string|max:250',

            'documents' => 'nullable|array',
            'documents.*.id' => ['nullable', 'integer', 'distinct', Rule::exists('licence_type_documents', 'id')->where('licence_type_id', $licenceType?->getKey() ?? 0)],
            'documents.*.name' => 'required|string|max:250',
            'documents.*.description' => 'nullable|string|max:1000',
            'documents.*.kind' => ['required', Rule::enum(LicenceDocumentKind::class)],
            'documents.*.template' => [
                'exclude_unless:documents.*.kind,'.LicenceDocumentKind::SignedForm->value,
                'required',
                Rule::enum(LicenceFormTemplate::class),
            ],
            'documents.*.scope' => ['required', Rule::enum(LicenceDocumentScope::class)],
            'documents.*.underage_only' => 'nullable|boolean',
            'documents.*.required' => 'nullable|boolean',
        ];
    }

    /**
     * The attributes of the Licence Type itself.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function attributes(array $validated, Request $request): array
    {
        return [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'race_licence_category' => $validated['race_licence_category'] ?? null,
            'active' => $request->boolean('active'),
        ];
    }

    /**
     * The submitted grades in their submitted order.
     *
     * @param  array<int, array<string, mixed>>  $grades
     * @return array<int, array<string, mixed>>
     */
    private function gradeRows(array $grades): array
    {
        return collect($grades)
            ->sortKeys()
            ->values()
            ->map(fn (array $grade, int $index) => [
                'id' => $grade['id'] ?? null,
                'level' => $grade['level'] ?? null,
                'name' => $grade['name'],
                'sort' => $index,
            ])
            ->all();
    }

    /**
     * The submitted documents in their submitted order.
     *
     * @param  array<int, array<string, mixed>>  $documents
     * @return array<int, array<string, mixed>>
     */
    private function documentRows(array $documents): array
    {
        return collect($documents)
            ->sortKeys()
            ->values()
            ->map(fn (array $document, int $index) => [
                'id' => $document['id'] ?? null,
                'name' => $document['name'],
                'description' => $document['description'] ?? null,
                'kind' => $document['kind'],
                'template' => $document['template'] ?? null,
                'scope' => $document['scope'],
                'underage_only' => (bool) ($document['underage_only'] ?? false),
                'required' => (bool) ($document['required'] ?? false),
                'sort' => $index,
            ])
            ->all();
    }

    /**
     * Make the related rows match the submitted ones: update rows with an id, create the others and delete the missing.
     *
     * @template TRelated of \Illuminate\Database\Eloquent\Model
     *
     * @param  HasMany<TRelated, LicenceType>  $relation
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function syncRows(HasMany $relation, array $rows): void
    {
        $keptIds = collect($rows)->pluck('id')->filter()->all();

        (clone $relation)->whereNotIn('id', $keptIds)->delete();

        foreach ($rows as $row) {
            $attributes = collect($row)->except('id')->all();

            if ($row['id']) {
                (clone $relation)->whereKey($row['id'])->firstOrFail()->update($attributes);

                continue;
            }

            $relation->create($attributes);
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\LicenceRequests\ResubmitLicenceRequest;
use App\Actions\LicenceRequests\StoreLicenceRequestUpload;
use App\Actions\LicenceRequests\SubmitLicenceRequest;
use App\Data\HolderData;
use App\Data\RequiredDocument;
use App\Enums\LicenceDocumentKind;
use App\Enums\LicenceProvider;
use App\Enums\LicenceRequestSection;
use App\Enums\LicenceRequestStatus;
use App\Models\LicenceRequest;
use App\Rules\LicenseNumberValidationRule;
use App\Support\LicenceCatalogue;
use Illuminate\Contracts\View\View;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * The Licence Request form: an accordion of sections, each saved on its own.
 * After a change request only the flagged sections and documents can be changed.
 */
#[Layout('layouts.app')]
class LicenceRequestForm extends Component
{
    use WithFileUploads;

    public LicenceRequest $licenceRequest;

    /**
     * The open section, or "review".
     */
    public string $openSection = '';

    public string $provider = '';

    public string $licenceType = '';

    /**
     * Whether an ACI driver licence leaves out the competitor licence, which it includes otherwise.
     */
    public bool $driverOnly = false;

    public string $previousLicenceNumber = '';

    /** @var array<string, mixed> */
    public array $holder = [];

    /** @var array<int, array<string, mixed>> */
    public array $parents = [];

    /**
     * Files picked for upload, by required document key.
     *
     * @var array<string, UploadedFile|null>
     */
    public array $files = [];

    public bool $declared = false;

    public function mount(LicenceRequest $licenceRequest): void
    {
        $this->authorize('update', $licenceRequest);

        $this->licenceRequest = $licenceRequest;

        $this->provider = $licenceRequest->provider->value ?? '';
        $this->licenceType = $licenceRequest->licence_type ?? '';
        $this->driverOnly = $licenceRequest->licence_type !== null && ! $licenceRequest->includes_competitor;
        $this->previousLicenceNumber = $licenceRequest->previous_licence_number ?? '';
        $this->holder = $licenceRequest->holder?->toArray() ?? $this->blankPerson();
        $this->parents = array_map(fn (HolderData $parent) => $parent->toArray(), $licenceRequest->parentHolders());

        $this->openSection = $this->nextSection();
    }

    public function open(string $section): void
    {
        $this->openSection = $section;
    }

    /**
     * Another provider has other Licence Types.
     */
    public function updatedProvider(): void
    {
        $this->licenceType = '';
        $this->driverOnly = false;
    }

    /**
     * Save the licence being renewed: provider, Licence Type, competitor licence and last year's number.
     */
    public function saveLicence(): void
    {
        $this->ensureCanEdit(LicenceRequestSection::Licence);

        $this->validate([
            'provider' => ['required', Rule::enum(LicenceProvider::class)->only(LicenceCatalogue::providers())],
        ], attributes: ['provider' => __('Provider')]);

        $provider = LicenceProvider::from($this->provider);

        $currentLicenceType = $this->licenceRequest->provider === $provider ? $this->licenceRequest->licence_type : null;

        $validated = $this->validate([
            'licenceType' => ['required', 'string', Rule::in(LicenceCatalogue::selectableCodes($provider, $currentLicenceType))],
            'driverOnly' => ['boolean'],
            'previousLicenceNumber' => ['required', 'string', 'max:250', new LicenseNumberValidationRule],
        ], attributes: [
            'licenceType' => __('Licence type'),
            'previousLicenceNumber' => __('Last year\'s licence number'),
        ]);

        $licenceType = LicenceCatalogue::find($provider, $validated['licenceType']);

        abort_if($licenceType === null, 404);

        $this->licenceRequest->update([
            'provider' => $provider,
            'licence_type' => $licenceType->code,
            'includes_competitor' => ! $validated['driverOnly'] && $licenceType->allowsCompetitor(),
            'previous_licence_number' => $validated['previousLicenceNumber'],
        ]);

        $this->licenceRequest->forgetUploadsNoLongerAskedFor();

        $this->completeSection(LicenceRequestSection::Licence);
    }

    public function saveHolder(): void
    {
        $this->ensureCanEdit(LicenceRequestSection::Holder);

        $validated = $this->validate(HolderData::validationRules(['holder' => $this->holder]), attributes: $this->holderAttributes('holder'));

        $this->licenceRequest->update([
            'holder' => HolderData::fromValidated($validated['holder']),
        ]);

        if (! $this->licenceRequest->isUnderage()) {
            $this->licenceRequest->update(['parents' => []]);
            $this->parents = [];
        }

        $this->licenceRequest->forgetUploadsNoLongerAskedFor();

        $this->completeSection(LicenceRequestSection::Holder);
    }

    public function addParent(): void
    {
        if (count($this->parents) < 2) {
            $this->parents[] = $this->blankPerson();
        }
    }

    /**
     * Remove the second parent or guardian. The first one is required, its details can only be changed.
     */
    public function removeParent(int $index): void
    {
        abort_unless($index === 1, 404);

        unset($this->parents[$index]);

        $this->parents = array_values($this->parents);
    }

    public function saveParents(): void
    {
        $this->ensureCanEdit(LicenceRequestSection::Parents);

        if ($this->parents === []) {
            $this->addError('parents', __('Add at least one parent or guardian.'));

            return;
        }

        $rules = collect(array_keys($this->parents))
            ->flatMap(fn (int $index) => HolderData::validationRules(['parents' => $this->parents], "parents.{$index}"))
            ->all();

        $attributes = collect(array_keys($this->parents))
            ->flatMap(fn (int $index) => $this->holderAttributes("parents.{$index}"))
            ->all();

        $validated = $this->validate(['parents' => ['required', 'array', 'min:1', 'max:2'], ...$rules], attributes: $attributes);

        $this->licenceRequest->update([
            'parents' => array_map(fn (array $parent) => HolderData::fromValidated($parent), array_values($validated['parents'])),
        ]);

        $this->licenceRequest->forgetUploadsNoLongerAskedFor();

        $this->completeSection(LicenceRequestSection::Parents);
    }

    /**
     * Store a document or signed form as soon as its file is picked. Uploading after a change request on the section resolves it.
     */
    public function updatedFiles(mixed $file, string $key): void
    {
        $this->authorize('update', $this->licenceRequest);

        $required = $this->findRequiredDocument($key);

        abort_if($required === null, 404);

        $section = LicenceRequestSection::forDocumentKind($required->document->kind());

        $this->ensureCanEdit($section);

        if (! $file instanceof UploadedFile) {
            return;
        }

        try {
            app(StoreLicenceRequestUpload::class)($this->licenceRequest, $required, $file);
        } catch (ValidationException $exception) {
            $this->addError("files.{$key}", $exception->validator->errors()->first());

            return;
        }

        unset($this->files[$key]);

        $this->licenceRequest->resolveChangeRequests($section);

        $this->licenceRequest->refresh();
    }

    /**
     * Move on from a section without fields: documents or forms. Change requests are resolved by uploading, not here.
     */
    public function continue(string $section): void
    {
        $section = LicenceRequestSection::from($section);

        abort_unless(in_array($section, [LicenceRequestSection::Documents, LicenceRequestSection::Forms], true), 404);

        $this->ensureCanEdit($section);

        $this->openSection = $this->nextSection($section);
    }

    public function submit(SubmitLicenceRequest $submit, ResubmitLicenceRequest $resubmit): void
    {
        $this->authorize('update', $this->licenceRequest);

        if ($this->licenceRequest->status === LicenceRequestStatus::Draft) {
            $this->validate(['declared' => ['accepted']], attributes: ['declared' => __('declaration')]);

            $submit($this->licenceRequest);
        } else {
            $resubmit($this->licenceRequest);
        }

        $this->redirectRoute('licence-requests.show', $this->licenceRequest, navigate: false);
    }

    public function render(): View
    {
        $licenceRequest = $this->licenceRequest->load(['documents', 'changeRequests', 'latestPayment', 'coveringEnrollment.championship']);

        $provider = LicenceProvider::tryFrom($this->provider);

        $licenceTypes = $provider === null
            ? collect()
            : LicenceCatalogue::selectableTypes($provider, $licenceRequest->provider === $provider ? $licenceRequest->licence_type : null);

        return view('livewire.licence-request-form', [
            'sections' => $licenceRequest->sections(),
            'providers' => LicenceCatalogue::providers(),
            'licenceTypes' => $licenceTypes,
            'selectedLicenceType' => $licenceTypes->firstWhere('code', $this->licenceType),
            'documents' => $licenceRequest->requiredDocuments(LicenceDocumentKind::Upload),
            'forms' => $licenceRequest->requiredDocuments(LicenceDocumentKind::SignedForm),
            'renewalPrice' => LicenceCatalogue::renewalPriceLabel(),
        ])->title(__('Licence request'));
    }

    /**
     * The state shown on a section header: done, todo, flagged, fixed or accepted.
     */
    public function sectionState(LicenceRequestSection $section): string
    {
        $licenceRequest = $this->licenceRequest;

        if ($licenceRequest->status === LicenceRequestStatus::ChangesRequested) {
            return match (true) {
                $licenceRequest->isSectionFlagged($section) => 'flagged',
                $licenceRequest->wasSectionFlagged($section) => 'fixed',
                default => 'accepted',
            };
        }

        return $licenceRequest->isSectionComplete($section) ? 'done' : 'todo';
    }

    /**
     * The one-line summary shown on a closed section.
     */
    public function sectionSummary(LicenceRequestSection $section): string
    {
        $licenceRequest = $this->licenceRequest;

        return match ($section) {
            LicenceRequestSection::Licence => collect([
                $licenceRequest->provider?->label(),
                $licenceRequest->licenceTypeLabel(),
            ])->filter()->join(' · '),
            LicenceRequestSection::Holder => $licenceRequest->holder?->fullName() ?? '',
            LicenceRequestSection::Parents => collect($licenceRequest->parentHolders())->map->fullName()->join(', '),
            LicenceRequestSection::Documents => $this->uploadedCount(LicenceDocumentKind::Upload),
            LicenceRequestSection::Forms => $this->uploadedCount(LicenceDocumentKind::SignedForm),
        };
    }

    /**
     * An empty person whose address already has its keys. An empty array reaches the browser as a JSON array,
     * where the address fields set by wire:model are dropped when sent back, so the first save failed validation.
     *
     * @return array{residence_address: array{address: string, city: string, province: string, postal_code: string}}
     */
    private function blankPerson(): array
    {
        return [
            'residence_address' => [
                'address' => '',
                'city' => '',
                'province' => '',
                'postal_code' => '',
            ],
        ];
    }

    private function uploadedCount(LicenceDocumentKind $kind): string
    {
        $required = $this->licenceRequest->requiredDocuments($kind);

        return __(':uploaded of :total uploaded', [
            'uploaded' => $required->filter(fn (RequiredDocument $document) => $this->licenceRequest->uploadFor($document) !== null)->count(),
            'total' => $required->count(),
        ]);
    }

    private function findRequiredDocument(string $key): ?RequiredDocument
    {
        return $this->licenceRequest->allRequiredDocuments()
            ->first(fn (RequiredDocument $required) => $required->key() === $key);
    }

    private function ensureCanEdit(LicenceRequestSection $section): void
    {
        $this->authorize('update', $this->licenceRequest);

        abort_unless($this->licenceRequest->canEditSection($section), 403);
    }

    /**
     * After saving a section: its change requests are resolved and the next section to fill in opens.
     */
    private function completeSection(LicenceRequestSection $section): void
    {
        $this->licenceRequest->resolveChangeRequests($section);

        $this->licenceRequest->refresh();

        $this->openSection = $this->nextSection($section);
    }

    /**
     * The first section after the given one still to fill in, or flagged after a change request. Review when none.
     */
    private function nextSection(?LicenceRequestSection $after = null): string
    {
        $sections = $this->licenceRequest->sections();

        $start = $after === null ? 0 : array_search($after, $sections, true) + 1;

        $isChangesRequested = $this->licenceRequest->status === LicenceRequestStatus::ChangesRequested;

        foreach (array_slice($sections, (int) $start) as $section) {
            $isIncomplete = ! $this->licenceRequest->isSectionComplete($section);

            $needsWork = $isIncomplete || ($isChangesRequested && $this->licenceRequest->isSectionFlagged($section));

            if ($needsWork) {
                return $section->value;
            }
        }

        return 'review';
    }

    /**
     * Readable names for the holder fields in validation messages.
     *
     * @return array<string, string>
     */
    private function holderAttributes(string $prefix): array
    {
        return [
            "{$prefix}.first_name" => __('First name'),
            "{$prefix}.last_name" => __('Last name'),
            "{$prefix}.sex" => __('Sex'),
            "{$prefix}.birth_date" => __('Birth date'),
            "{$prefix}.birth_place" => __('Birth place'),
            "{$prefix}.fiscal_code" => __('Fiscal code'),
            "{$prefix}.nationality" => __('Nationality'),
            "{$prefix}.email" => __('E-Mail'),
            "{$prefix}.phone" => __('Phone number'),
            "{$prefix}.residence_address.address" => __('Residence address'),
            "{$prefix}.residence_address.city" => __('City'),
            "{$prefix}.residence_address.province" => __('Province'),
            "{$prefix}.residence_address.postal_code" => __('Postal code'),
        ];
    }
}

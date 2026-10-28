@php
    $licenceType = $licenceType ?? null;

    $grades = old('grades', $licenceType?->grades->map(fn ($grade) => [
        'id' => $grade->getKey(),
        'level' => $grade->level?->value,
        'name' => $grade->name,
    ])->all() ?? []);

    $documents = old('documents', $licenceType?->documents->map(fn ($document) => [
        'id' => $document->getKey(),
        'name' => $document->name,
        'description' => $document->description,
        'kind' => $document->kind->value,
        'template' => $document->template?->value,
        'scope' => $document->scope->value,
        'underage_only' => $document->underage_only,
        'required' => $document->required,
    ])->all() ?? []);

    // Checkboxes come back from old input as "0" or "1"; Alpine needs real booleans to tick them
    $documents = array_map(fn (array $document) => [
        ...$document,
        'underage_only' => filter_var($document['underage_only'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'required' => filter_var($document['required'] ?? false, FILTER_VALIDATE_BOOLEAN),
    ], $documents);

    $active = session()->hasOldInput() ? (bool) old('active') : ($licenceType?->active ?? true);

    $inputClass = 'block w-full rounded-md border-zinc-300 text-sm shadow-xs focus:border-orange-300 focus:ring-3 focus:ring-orange-200/50';
@endphp

<div
    x-data="{
        provider: @js(old('provider', $licenceType?->provider->value ?? \App\Enums\LicenceProvider::Aci->value)),
        role: @js(old('role', $licenceType?->role->value ?? \App\Enums\LicenceRole::Driver->value)),
        grades: @js(array_values($grades)),
        documents: @js(array_values($documents)),
        errors: @js($errors->getMessages()),
        error(key) { return (this.errors[key] ?? [])[0] ?? null },
        addGrade() { this.grades.push({ id: null, level: this.provider === 'aci' ? 'national' : null, name: '' }) },
        addDocument() { this.documents.push({ id: null, name: '', description: '', kind: 'upload', template: null, scope: 'holder', underage_only: false, required: true }) },
        move(list, index, offset) {
            const target = index + offset;
            if (target < 0 || target >= list.length) { return }
            list.splice(target, 0, list.splice(index, 1)[0]);
        },
    }"
>

    <div class="md:grid md:grid-cols-3 md:gap-6">
        <x-section-title>
            <x-slot name="title">{{ __('Details') }}</x-slot>
            <x-slot name="description">{{ __('Provider and role cannot be changed once the licence type is created.') }}</x-slot>
        </x-section-title>

        <div class="mt-5 md:mt-0 md:col-span-2">
            <div class="px-4 py-5">
                <div class="grid grid-cols-6 gap-6">
                    <div class="col-span-6 sm:col-span-3">
                        <x-label for="provider" value="{{ __('Provider') }}*" />
                        @if ($licenceType)
                            <p class="mt-1 text-sm">{{ $licenceType->provider->label() }}</p>
                        @else
                            <select id="provider" name="provider" x-model="provider" class="mt-1 {{ $inputClass }}">
                                @foreach (\App\Enums\LicenceProvider::options() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        @endif
                        <x-input-error for="provider" class="mt-2" />
                    </div>
                    <div class="col-span-6 sm:col-span-3">
                        <x-label for="role" value="{{ __('Role') }}*" />
                        @if ($licenceType)
                            <p class="mt-1 text-sm">{{ $licenceType->role->label() }}</p>
                        @else
                            <select id="role" name="role" x-model="role" class="mt-1 {{ $inputClass }}">
                                @foreach (\App\Enums\LicenceRole::options() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        @endif
                        <x-input-error for="role" class="mt-2" />
                    </div>
                    <div class="col-span-6 sm:col-span-4">
                        <x-label for="name" value="{{ __('Name') }}*" />
                        <x-input id="name" type="text" name="name" :value="old('name', $licenceType?->name)" class="mt-1 block w-full" required />
                        <x-input-error for="name" class="mt-2" />
                    </div>
                    <div class="col-span-6 sm:col-span-4">
                        <x-label for="description" value="{{ __('Description') }}" />
                        <x-input id="description" type="text" name="description" :value="old('description', $licenceType?->description)" class="mt-1 block w-full" />
                        <x-input-error for="description" class="mt-2" />
                    </div>
                    <template x-if="provider === 'asi' && role === 'driver'">
                        <div class="col-span-6 sm:col-span-4">
                            <x-label for="race_licence_category" value="{{ __('Race registration licence') }}*" />
                            <p class="text-zinc-600 text-sm">{{ __('The licence category this licence counts as when registering for a race.') }}</p>
                            <select id="race_licence_category" name="race_licence_category" class="mt-1 {{ $inputClass }}">
                                @foreach ([\App\Models\DriverLicence::LOCAL_NATIONAL, \App\Models\DriverLicence::LOCAL_INTERNATIONAL] as $category)
                                    <option value="{{ $category->value }}" @selected((int) old('race_licence_category', $licenceType?->race_licence_category?->value) === $category->value)>{{ $category->localizedName() }}</option>
                                @endforeach
                            </select>
                            <x-input-error for="race_licence_category" class="mt-2" />
                        </div>
                    </template>
                    <div class="col-span-6 sm:col-span-4">
                        <label for="active" class="flex items-center">
                            <x-checkbox id="active" name="active" value="1" :checked="$active" />
                            <span class="ml-2">{{ __('Offer this licence for new requests') }}</span>
                        </label>
                        <p class="text-zinc-600 text-sm">{{ __('Inactive licence types are kept on existing requests.') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-section-border />

    <div class="md:grid md:grid-cols-3 md:gap-6">
        <x-section-title>
            <x-slot name="title">{{ __('Grades') }}</x-slot>
            <x-slot name="description">
                <span x-show="provider === 'aci'">{{ __('The common grades for each level, e.g. C Junior for national. Applicants pick one or write another.') }}</span>
                <span x-show="provider !== 'aci'">{{ __('The common grades applicants can pick from. Applicants can also write another.') }}</span>
            </x-slot>
        </x-section-title>

        <div class="mt-5 md:mt-0 md:col-span-2">
            <div class="px-4 py-5 space-y-3">
                <template x-for="(grade, index) in grades" :key="index">
                    <div class="flex flex-wrap items-start gap-2">
                        <input type="hidden" :name="`grades[${index}][id]`" :value="grade.id">
                        <template x-if="provider === 'aci'">
                            <select :name="`grades[${index}][level]`" x-model="grade.level" class="{{ $inputClass }} w-auto" aria-label="{{ __('Level') }}">
                                @foreach (\App\Enums\LicenceLevel::options() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </template>
                        <input type="text" :name="`grades[${index}][name]`" x-model="grade.name" class="{{ $inputClass }} flex-1 min-w-40" aria-label="{{ __('Grade') }}" required>
                        <button type="button" class="text-sm text-zinc-600 hover:text-zinc-900 px-1" @click="move(grades, index, -1)" title="{{ __('Move up') }}">↑</button>
                        <button type="button" class="text-sm text-zinc-600 hover:text-zinc-900 px-1" @click="move(grades, index, 1)" title="{{ __('Move down') }}">↓</button>
                        <button type="button" class="text-sm text-red-600 hover:text-red-900" @click="grades.splice(index, 1)">{{ __('Remove') }}</button>
                        <p class="w-full text-sm text-red-600" x-show="error(`grades.${index}.level`) || error(`grades.${index}.name`)" x-text="error(`grades.${index}.level`) ?? error(`grades.${index}.name`)"></p>
                    </div>
                </template>

                <x-secondary-button type="button" @click="addGrade()">{{ __('Add grade') }}</x-secondary-button>
            </div>
        </div>
    </div>

    <x-section-border />

    <div class="md:grid md:grid-cols-3 md:gap-6">
        <x-section-title>
            <x-slot name="title">{{ __('Documents') }}</x-slot>
            <x-slot name="description">{{ __('The documents and forms a request needs. Forms to sign are given to the applicant pre-filled with their details. Documents for each parent are asked once per parent or guardian entered.') }}</x-slot>
        </x-section-title>

        <div class="mt-5 md:mt-0 md:col-span-2">
            <div class="px-4 py-5 space-y-4">
                <template x-for="(document, index) in documents" :key="index">
                    <div class="grid grid-cols-6 gap-2 rounded-md border border-zinc-200 p-3">
                        <input type="hidden" :name="`documents[${index}][id]`" :value="document.id">

                        <div class="col-span-6 sm:col-span-3">
                            <label class="text-sm font-medium text-zinc-700" :for="`document-name-${index}`">{{ __('Name') }}*</label>
                            <input type="text" :id="`document-name-${index}`" :name="`documents[${index}][name]`" x-model="document.name" class="mt-1 {{ $inputClass }}" required>
                        </div>
                        <div class="col-span-6 sm:col-span-3">
                            <label class="text-sm font-medium text-zinc-700" :for="`document-description-${index}`">{{ __('Description') }}</label>
                            <input type="text" :id="`document-description-${index}`" :name="`documents[${index}][description]`" x-model="document.description" class="mt-1 {{ $inputClass }}">
                        </div>
                        <div class="col-span-6 sm:col-span-2">
                            <label class="text-sm font-medium text-zinc-700" :for="`document-kind-${index}`">{{ __('Kind') }}</label>
                            <select :id="`document-kind-${index}`" :name="`documents[${index}][kind]`" x-model="document.kind" class="mt-1 {{ $inputClass }}">
                                @foreach (\App\Enums\LicenceDocumentKind::options() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-6 sm:col-span-2" x-show="document.kind === 'signed_form'">
                            <label class="text-sm font-medium text-zinc-700" :for="`document-template-${index}`">{{ __('Form') }}*</label>
                            <select :id="`document-template-${index}`" :name="`documents[${index}][template]`" x-model="document.template" class="mt-1 {{ $inputClass }}" :disabled="document.kind !== 'signed_form'">
                                <option value="">{{ __('Choose a form') }}</option>
                                @foreach (\App\Enums\LicenceFormTemplate::options() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-6 sm:col-span-2">
                            <label class="text-sm font-medium text-zinc-700" :for="`document-scope-${index}`">{{ __('Asked for') }}</label>
                            <select :id="`document-scope-${index}`" :name="`documents[${index}][scope]`" x-model="document.scope" class="mt-1 {{ $inputClass }}">
                                @foreach (\App\Enums\LicenceDocumentScope::options() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-6 flex flex-wrap items-center gap-4">
                            <label class="flex items-center text-sm">
                                <input type="hidden" :name="`documents[${index}][required]`" value="0">
                                <input type="checkbox" :name="`documents[${index}][required]`" value="1" x-model="document.required" class="rounded-sm border-zinc-300 text-orange-600">
                                <span class="ml-2">{{ __('Required') }}</span>
                            </label>
                            <label class="flex items-center text-sm">
                                <input type="hidden" :name="`documents[${index}][underage_only]`" value="0">
                                <input type="checkbox" :name="`documents[${index}][underage_only]`" value="1" x-model="document.underage_only" class="rounded-sm border-zinc-300 text-orange-600">
                                <span class="ml-2">{{ __('Only when the holder is underage') }}</span>
                            </label>
                            <span class="flex-1"></span>
                            <button type="button" class="text-sm text-zinc-600 hover:text-zinc-900 px-1" @click="move(documents, index, -1)" title="{{ __('Move up') }}">↑</button>
                            <button type="button" class="text-sm text-zinc-600 hover:text-zinc-900 px-1" @click="move(documents, index, 1)" title="{{ __('Move down') }}">↓</button>
                            <button type="button" class="text-sm text-red-600 hover:text-red-900" @click="documents.splice(index, 1)">{{ __('Remove') }}</button>
                        </div>
                        <template x-for="field in ['name', 'description', 'kind', 'template', 'scope']">
                            <p class="col-span-6 text-sm text-red-600" x-show="error(`documents.${index}.${field}`)" x-text="error(`documents.${index}.${field}`)"></p>
                        </template>
                    </div>
                </template>

                <x-secondary-button type="button" @click="addDocument()">{{ __('Add document') }}</x-secondary-button>
            </div>
        </div>
    </div>
</div>

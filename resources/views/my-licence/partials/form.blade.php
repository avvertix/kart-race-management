@php
    $licence = $licence ?? null;

    $holder = old('holder', $licence ? [
        ...$licence->holder->toArray(),
        'sex' => $licence->holder->sex->value,
    ] : []);

    $types = $licenceTypes->map(fn ($type) => [
        'ulid' => $type->ulid,
        'provider' => $type->provider->value,
        'grades' => $type->grades->map(fn ($grade) => [
            'id' => $grade->getKey(),
            'level' => $grade->level?->value,
            'name' => $grade->name,
        ])->values(),
    ])->values();

    $currentYear = now()->year;

    $inputClass = 'block w-full rounded-md border-zinc-300 text-sm shadow-xs focus:border-orange-300 focus:ring-3 focus:ring-orange-200/50';
@endphp

<div
    x-data="{
        types: @js($types),
        licenceType: @js(old('licence_type', $licence?->licenceType?->ulid ?? '')),
        level: @js(old('level', $licence?->level?->value ?? 'national')),
        grade: @js((string) old('licence_grade', $licence?->licence_grade_id ?? '')),
        get type() { return this.types.find(type => type.ulid === this.licenceType) ?? null },
        get isAci() { return this.type?.provider === 'aci' },
        get grades() { return (this.type?.grades ?? []).filter(grade => ! this.isAci || grade.level === this.level) },
    }"
>
    <div class="md:grid md:grid-cols-3 md:gap-6">
        <x-section-title>
            <x-slot name="title">{{ __('Licence') }}</x-slot>
            <x-slot name="description">{{ __('An ACI or ASI licence obtained elsewhere, e.g. renewed directly with the provider. The organizer verifies it before confirming an enrollment with it.') }}</x-slot>
        </x-section-title>

        <div class="mt-5 md:mt-0 md:col-span-2">
            <div class="px-4 py-5">
                <div class="grid grid-cols-6 gap-6">
                    <div class="col-span-6 sm:col-span-4">
                        <x-label for="licence_type" value="{{ __('Licence type') }}*" />
                        <select id="licence_type" name="licence_type" x-model="licenceType" class="mt-1 {{ $inputClass }}" required>
                            <option value="">{{ __('Choose a licence type') }}</option>
                            @foreach ($licenceTypes as $type)
                                <option value="{{ $type->ulid }}">{{ $type->provider->label() }} &middot; {{ $type->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error for="licence_type" class="mt-2" />
                    </div>

                    <template x-if="isAci">
                        <div class="col-span-6 sm:col-span-2">
                            <x-label for="level" value="{{ __('Level') }}*" />
                            <select id="level" name="level" x-model="level" class="mt-1 {{ $inputClass }}">
                                @foreach (\App\Enums\LicenceLevel::options() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error for="level" class="mt-2" />
                        </div>
                    </template>

                    <div class="col-span-6 sm:col-span-3" x-show="type">
                        <x-label for="licence_grade" value="{{ __('Grade') }}*" />
                        <select id="licence_grade" name="licence_grade" x-model="grade" class="mt-1 {{ $inputClass }}">
                            <template x-for="option in grades" :key="option.id">
                                <option :value="String(option.id)" x-text="option.name" :selected="String(option.id) === grade"></option>
                            </template>
                            <option value="">{{ __('Other, specify') }}</option>
                        </select>
                        <x-input-error for="licence_grade" class="mt-2" />
                    </div>

                    <div class="col-span-6 sm:col-span-3" x-show="type && grade === ''">
                        <x-label for="grade_other" value="{{ __('Grade') }}*" />
                        <x-input id="grade_other" type="text" name="grade_other" :value="old('grade_other', $licence?->grade_other)" class="mt-1 block w-full" />
                        <x-input-error for="grade_other" class="mt-2" />
                    </div>

                    <div class="col-span-6 sm:col-span-3">
                        <x-label for="number" value="{{ __('Licence number') }}*" />
                        <x-input id="number" type="text" name="number" :value="old('number', $licence?->number)" class="mt-1 block w-full" required />
                        <x-input-error for="number" class="mt-2" />
                    </div>

                    <div class="col-span-6 sm:col-span-3">
                        <x-label for="validity_year" value="{{ __('Valid for the year') }}*" />
                        <select id="validity_year" name="validity_year" class="mt-1 {{ $inputClass }}">
                            @foreach ([$currentYear, $currentYear + 1] as $year)
                                <option value="{{ $year }}" @selected((int) old('validity_year', $licence?->validity_year ?? $currentYear) === $year)>{{ $year }}</option>
                            @endforeach
                        </select>
                        <x-input-error for="validity_year" class="mt-2" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-section-border />

    <div class="md:grid md:grid-cols-3 md:gap-6">
        <x-section-title>
            <x-slot name="title">{{ __('Holder') }}</x-slot>
            <x-slot name="description">{{ __('The person the licence belongs to, as written on the licence.') }}</x-slot>
        </x-section-title>

        <div class="mt-5 md:mt-0 md:col-span-2">
            <div class="px-4 py-5">
                <div class="grid grid-cols-6 gap-6">
                    <div class="col-span-6 sm:col-span-3">
                        <x-label for="holder_first_name" value="{{ __('First name') }}*" />
                        <x-input id="holder_first_name" type="text" name="holder[first_name]" :value="$holder['first_name'] ?? null" class="mt-1 block w-full" required />
                        <x-input-error for="holder.first_name" class="mt-2" />
                    </div>
                    <div class="col-span-6 sm:col-span-3">
                        <x-label for="holder_last_name" value="{{ __('Last name') }}*" />
                        <x-input id="holder_last_name" type="text" name="holder[last_name]" :value="$holder['last_name'] ?? null" class="mt-1 block w-full" required />
                        <x-input-error for="holder.last_name" class="mt-2" />
                    </div>
                    <div class="col-span-6 sm:col-span-2">
                        <x-label for="holder_sex" value="{{ __('Sex') }}*" />
                        <select id="holder_sex" name="holder[sex]" class="mt-1 {{ $inputClass }}">
                            @foreach ([\App\Models\Sex::MALE, \App\Models\Sex::FEMALE] as $sex)
                                <option value="{{ $sex->value }}" @selected((int) ($holder['sex'] ?? 0) === $sex->value)>{{ $sex->localizedName() }}</option>
                            @endforeach
                        </select>
                        <x-input-error for="holder.sex" class="mt-2" />
                    </div>
                    <div class="col-span-6 sm:col-span-2">
                        <x-label for="holder_birth_date" value="{{ __('Birth date') }}*" />
                        <x-input-date id="holder_birth_date" name="holder[birth_date]" :value="$holder['birth_date'] ?? null" class="mt-1 block w-full" required />
                        <x-input-error for="holder.birth_date" class="mt-2" />
                    </div>
                    <div class="col-span-6 sm:col-span-2">
                        <x-label for="holder_birth_place" value="{{ __('Birth place') }}*" />
                        <x-input id="holder_birth_place" type="text" name="holder[birth_place]" :value="$holder['birth_place'] ?? null" class="mt-1 block w-full" required />
                        <x-input-error for="holder.birth_place" class="mt-2" />
                    </div>
                    <div class="col-span-6 sm:col-span-3">
                        <x-label for="holder_nationality" value="{{ __('Nationality') }}*" />
                        <x-nationality-input id="holder_nationality" name="holder[nationality]" class="mt-1 block w-full" :value="$holder['nationality'] ?? null" required />
                        <x-input-error for="holder.nationality" class="mt-2" />
                    </div>
                    <div class="col-span-6 sm:col-span-3">
                        <x-label for="holder_fiscal_code" value="{{ __('Fiscal code') }}" />
                        <x-input id="holder_fiscal_code" type="text" name="holder[fiscal_code]" :value="$holder['fiscal_code'] ?? null" class="mt-1 block w-full" />
                        <x-input-error for="holder.fiscal_code" class="mt-2" />
                    </div>
                    <div class="col-span-6 sm:col-span-3">
                        <x-label for="holder_email" value="{{ __('E-Mail') }}*" />
                        <x-input id="holder_email" type="email" name="holder[email]" :value="$holder['email'] ?? null" class="mt-1 block w-full" required />
                        <x-input-error for="holder.email" class="mt-2" />
                    </div>
                    <div class="col-span-6 sm:col-span-3">
                        <x-label for="holder_phone" value="{{ __('Phone number') }}*" />
                        <x-input id="holder_phone" type="text" name="holder[phone]" :value="$holder['phone'] ?? null" class="mt-1 block w-full" required />
                        <x-input-error for="holder.phone" class="mt-2" />
                    </div>
                    <div class="col-span-6">
                        <x-label for="holder_residence_address" value="{{ __('Residence address') }}*" />
                        <x-input id="holder_residence_address" type="text" name="holder[residence_address][address]" :value="$holder['residence_address']['address'] ?? null" class="mt-1 block w-full" required />
                        <x-input-error for="holder.residence_address.address" class="mt-2" />
                    </div>
                    <div class="col-span-6 sm:col-span-2">
                        <x-label for="holder_residence_city" value="{{ __('City') }}*" />
                        <x-input id="holder_residence_city" type="text" name="holder[residence_address][city]" :value="$holder['residence_address']['city'] ?? null" class="mt-1 block w-full" required />
                        <x-input-error for="holder.residence_address.city" class="mt-2" />
                    </div>
                    <div class="col-span-6 sm:col-span-2">
                        <x-label for="holder_residence_postal_code" value="{{ __('Postal code') }}*" />
                        <x-input id="holder_residence_postal_code" type="text" name="holder[residence_address][postal_code]" :value="$holder['residence_address']['postal_code'] ?? null" class="mt-1 block w-full" required />
                        <x-input-error for="holder.residence_address.postal_code" class="mt-2" />
                    </div>
                    <div class="col-span-6 sm:col-span-2">
                        <x-label for="holder_residence_province" value="{{ __('Province') }}" />
                        <x-input id="holder_residence_province" type="text" name="holder[residence_address][province]" :value="$holder['residence_address']['province'] ?? null" class="mt-1 block w-full" />
                        <x-input-error for="holder.residence_address.province" class="mt-2" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

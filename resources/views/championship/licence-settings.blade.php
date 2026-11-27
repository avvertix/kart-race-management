<x-championship-page-layout :$championship>
    <x-slot name="title">
        {{ __('Accepted licences') }} - {{ $championship->title }}
    </x-slot>

    <x-validation-errors class="mb-4" />

    <form method="POST" action="{{ route('championships.licence-settings.update', $championship) }}">
        @csrf
        @method('PUT')

        @feature(\App\Features\LicencesAndEnrollments::class)
            <div class="md:grid md:grid-cols-3 md:gap-6">
                <x-section-title>
                    <x-slot name="title">{{ __('Licence provider') }}</x-slot>
                    <x-slot name="description">
                        {{ __('Who issues the licences. It sets the wording of the registration form and which exports are offered.') }}
                    </x-slot>
                </x-section-title>

                <div class="mt-5 md:mt-0 md:col-span-2">
                    <div class="px-4 py-5">
                        @include('championship.partials.licence-provider', ['championship' => $championship])
                    </div>
                </div>
            </div>

            <x-section-border />
        @endfeature

        <div class="md:grid md:grid-cols-3 md:gap-6">
            <x-section-title>
                <x-slot name="title">{{ __('Driver licences') }}</x-slot>
                <x-slot name="description">
                    {{ __('Select which driver licence types are accepted for registration. Leave all unchecked to accept any licence type.') }}
                </x-slot>
            </x-section-title>

            <div class="mt-5 md:mt-0 md:col-span-2">
                <div class="px-4 py-5 space-y-3">
                    @foreach (\App\Models\DriverLicence::cases() as $licence)
                        <label class="flex items-start gap-3">
                            <x-checkbox
                                name="accepted_driver_licences[]"
                                value="{{ $licence->value }}"
                                :checked="in_array($licence->value, $championship->licences->accepted_driver_licences)"
                            />
                            <div>
                                <span class="text-sm font-medium text-zinc-900">{{ $licence->localizedName() }}</span>
                                <p class="text-sm text-zinc-500">{{ $licence->description($championship->licenceProviderName()) }}</p>
                            </div>
                        </label>
                    @endforeach
                    <x-input-error for="accepted_driver_licences" class="mt-2" />
                </div>
            </div>
        </div>

        <x-section-border />

        <div class="md:grid md:grid-cols-3 md:gap-6">
            <x-section-title>
                <x-slot name="title">{{ __('Competitor licences') }}</x-slot>
                <x-slot name="description">
                    {{ __('Select which competitor licence types are accepted for registration. Leave all unchecked to accept any licence type.') }}
                </x-slot>
            </x-section-title>

            <div class="mt-5 md:mt-0 md:col-span-2">
                <div class="px-4 py-5 space-y-3">
                    @foreach (\App\Models\CompetitorLicence::cases() as $licence)
                        <label class="flex items-start gap-3">
                            <x-checkbox
                                name="accepted_competitor_licences[]"
                                value="{{ $licence->value }}"
                                :checked="in_array($licence->value, $championship->licences->accepted_competitor_licences)"
                            />
                            <div>
                                <span class="text-sm font-medium text-zinc-900">{{ $licence->localizedName() }}</span>
                                <p class="text-sm text-zinc-500">{{ $licence->description($championship->licenceProviderName()) }}</p>
                            </div>
                        </label>
                    @endforeach
                    <x-input-error for="accepted_competitor_licences" class="mt-2" />
                </div>
            </div>
        </div>

        <div class="md:grid md:grid-cols-3 md:gap-6 mt-6">
            <div></div>
            <div class="mt-5 md:mt-0 md:col-span-2">
                <div class="px-4 py-5">
                    <x-button type="submit">
                        {{ __('Save') }}
                    </x-button>
                </div>
            </div>
        </div>

    </form>

</x-championship-page-layout>

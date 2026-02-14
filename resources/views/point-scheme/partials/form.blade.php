@php
    $runTypeLabels = [
        \App\Models\RunType::QUALIFY->value => __('Qualifying'),
        \App\Models\RunType::RACE_1->value => __('Race 1'),
        \App\Models\RunType::RACE_2->value => __('Race 2'),
    ];

    $statusLabels = [
        \App\Models\ResultStatus::DID_NOT_START->value => __('DNS (Did Not Start)'),
        \App\Models\ResultStatus::DID_NOT_FINISH->value => __('DNF (Did Not Finish)'),
        \App\Models\ResultStatus::DISQUALIFIED->value => __('DSQ (Disqualified)'),
    ];

    $existingConfig = old('points_config', optional($pointScheme ?? null)->points_config);
@endphp

<div class="md:grid md:grid-cols-3 md:gap-6">
    <x-section-title>
        <x-slot name="title">{{ __('Details') }}</x-slot>
        <x-slot name="description">{{ __('Point scheme name.') }}</x-slot>
    </x-section-title>

    <div class="mt-5 md:mt-0 md:col-span-2">

        <div class="px-4 py-5">
            <div class="grid grid-cols-6 gap-6">
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="name" value="{{ __('Name') }}*" />
                    <x-input id="name" type="text" name="name" :value="old('name', optional($pointScheme ?? null)->name)" class="mt-1 block w-full" required autocomplete="name" />
                    <x-input-error for="name" class="mt-2" />
                </div>
            </div>
        </div>
    </div>
</div>

<x-section-border />

@foreach ($runTypes as $runType)
    <div class="md:grid md:grid-cols-3 md:gap-6" x-data="{
        positions: @js($existingConfig[$runType->value]['positions'] ?? []),
        addPosition() {
            this.positions.push(0);
        },
        removePosition(index) {
            this.positions.splice(index, 1);
        }
    }">
        <x-section-title>
            <x-slot name="title">{{ $runTypeLabels[$runType->value] }}</x-slot>
            <x-slot name="description">{{ __('Points configuration for :run.', ['run' => $runTypeLabels[$runType->value]]) }}</x-slot>
        </x-section-title>

        <div class="mt-5 md:mt-0 md:col-span-2">

            <div class="px-4 py-5">
                <div class="grid grid-cols-6 gap-6">
                    <div class="col-span-6">
                        <h4 class="text-sm font-medium text-zinc-700">{{ __('Position Points') }}</h4>
                        <p class="text-sm text-zinc-500">{{ __('Points awarded by finishing position. First entry is for 1st place.') }}</p>

                        <div class="mt-3 space-y-2">
                            <template x-for="(points, index) in positions" :key="index">
                                <div class="flex items-center gap-3">
                                    <span class="text-sm text-zinc-600 w-8" x-text="(index + 1) + '.'"></span>
                                    <input type="number"
                                        :name="'points_config[{{ $runType->value }}][positions][' + index + ']'"
                                        x-model.number="positions[index]"
                                        min="0"
                                        step="any"
                                        class="border-zinc-300 focus:border-orange-500 focus:ring-orange-500 rounded-md shadow-sm w-24">
                                    <button type="button" @click="removePosition(index)" class="text-red-500 hover:text-red-700 text-sm">
                                        {{ __('Remove') }}
                                    </button>
                                </div>
                            </template>
                        </div>

                        <button type="button" @click="addPosition()" class="mt-3 text-sm text-orange-600 hover:text-orange-800">
                            + {{ __('Add position') }}
                        </button>

                        <x-input-error for="points_config.{{ $runType->value }}.positions" class="mt-2" />
                    </div>

                    <div class="col-span-6">
                        <h4 class="text-sm font-medium text-zinc-700">{{ __('Status Points') }}</h4>

                        <div class="mt-3 space-y-3">
                            @foreach ($resultStatuses as $status)
                                <div class="flex items-center gap-3">
                                    <x-label for="status_{{ $runType->value }}_{{ $status->value }}" class="w-48" value="{{ $statusLabels[$status->value] }}" />
                                    <x-input
                                        id="status_{{ $runType->value }}_{{ $status->value }}"
                                        type="number"
                                        name="points_config[{{ $runType->value }}][statuses][{{ $status->value }}]"
                                        :value="$existingConfig[$runType->value]['statuses'][$status->value] ?? 0"
                                        min="0"
                                        step="any"
                                        class="w-24" />
                                </div>
                            @endforeach
                        </div>

                        <x-input-error for="points_config.{{ $runType->value }}.statuses" class="mt-2" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (!$loop->last)
        <x-section-border />
    @endif
@endforeach

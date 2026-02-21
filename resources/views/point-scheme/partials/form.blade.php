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

    $pointsConfig = optional($pointScheme ?? null)->points_config;
    $existingConfig = old('points_config', $pointsConfig instanceof \App\Data\PointsConfigData ? $pointsConfig->toConfig() : null);
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

<div class="md:grid md:grid-cols-3 md:gap-6">
    <x-section-title>
        <x-slot name="title">{{ __('Modifiers') }}</x-slot>
        <x-slot name="description">{{ __('Global point modifiers applied to all run types.') }}</x-slot>
    </x-section-title>

    <div class="mt-5 md:mt-0 md:col-span-2">

        <div class="px-4 py-5">
            <div class="grid grid-cols-6 gap-6">
                <div class="col-span-6 sm:col-span-4">
                    <x-label for="rain_percentage" value="{{ __('Rain race modifier') }}" />
                    <p class="text-sm text-zinc-500">{{ __('Percentage applied to points when the race is classified as a rain race.') }}</p>
                    <div class="mt-1 flex items-center gap-2">
                        <x-input
                            id="rain_percentage"
                            type="number"
                            name="points_config[rain_percentage]"
                            :value="old('points_config.rain_percentage', $existingConfig['rain_percentage'] ?? 50)"
                            step="any"
                            class="w-24" />
                        <span class="text-sm text-zinc-600">%</span>
                    </div>
                    <x-input-error for="points_config.rain_percentage" class="mt-2" />
                </div>

                <div class="col-span-6 sm:col-span-4">
                    <x-label for="small_category_percentage" value="{{ __('Small category modifier') }}" />
                    <p class="text-sm text-zinc-500">{{ __('Percentage applied to points when a category has fewer participants than the threshold.') }}</p>
                    <div class="mt-1 flex items-center gap-2">
                        <span class="text-sm text-zinc-600">{{ __('Less than') }}</span>
                        <x-input
                            id="small_category_threshold"
                            type="number"
                            name="points_config[small_category_threshold]"
                            :value="old('points_config.small_category_threshold', $existingConfig['small_category_threshold'] ?? 3)"
                            min="1"
                            step="1"
                            class="w-20" />
                        <span class="text-sm text-zinc-600">{{ __('participants') }}</span>
                        <span class="text-sm text-zinc-600 mx-1">&rarr;</span>
                        <x-input
                            id="small_category_percentage"
                            type="number"
                            name="points_config[small_category_percentage]"
                            :value="old('points_config.small_category_percentage', $existingConfig['small_category_percentage'] ?? -50)"
                            step="any"
                            class="w-24" />
                        <span class="text-sm text-zinc-600">%</span>
                    </div>
                    <x-input-error for="points_config.small_category_percentage" class="mt-2" />
                    <x-input-error for="points_config.small_category_threshold" class="mt-2" />
                </div>
            </div>
        </div>
    </div>
</div>

<x-section-border />

@foreach ($runTypes as $runType)
    <div class="md:grid md:grid-cols-3 md:gap-6" x-data="{
        positions: @js($existingConfig[$runType->value]['positions'] ?? []),
        statusModes: {
            '{{ \App\Models\ResultStatus::DID_NOT_START->value }}': @js($existingConfig[$runType->value]['statuses'][\App\Models\ResultStatus::DID_NOT_START->value]['mode'] ?? 'fixed'),
            '{{ \App\Models\ResultStatus::DID_NOT_FINISH->value }}': @js($existingConfig[$runType->value]['statuses'][\App\Models\ResultStatus::DID_NOT_FINISH->value]['mode'] ?? 'fixed'),
        },
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

                        <div class="mt-3 space-y-4">
                            @foreach ($resultStatuses as $status)
                                @if ($status === \App\Models\ResultStatus::DISQUALIFIED)
                                    <div class="flex items-center gap-3">
                                        <x-label for="status_{{ $runType->value }}_{{ $status->value }}" class="w-48" value="{{ $statusLabels[$status->value] }}" />
                                        <input type="hidden" name="points_config[{{ $runType->value }}][statuses][{{ $status->value }}][mode]" value="fixed">
                                        <x-input
                                            id="status_{{ $runType->value }}_{{ $status->value }}"
                                            type="number"
                                            name="points_config[{{ $runType->value }}][statuses][{{ $status->value }}][points]"
                                            :value="$existingConfig[$runType->value]['statuses'][$status->value]['points'] ?? 0"
                                            min="0"
                                            step="any"
                                            class="w-24" />
                                    </div>
                                @else
                                    <div>
                                        <x-label class="mb-1" value="{{ $statusLabels[$status->value] }}" />
                                        <div class="flex items-center gap-3">
                                            <select
                                                name="points_config[{{ $runType->value }}][statuses][{{ $status->value }}][mode]"
                                                x-model="statusModes['{{ $status->value }}']"
                                                class="border-zinc-300 focus:border-orange-500 focus:ring-orange-500 rounded-md shadow-sm text-sm">
                                                <option value="fixed">{{ __('Fixed points') }}</option>
                                                <option value="ranked">{{ __('Points as ranked') }}</option>
                                            </select>
                                            <div x-show="statusModes['{{ $status->value }}'] === 'fixed'">
                                                <x-input
                                                    id="status_{{ $runType->value }}_{{ $status->value }}"
                                                    type="number"
                                                    name="points_config[{{ $runType->value }}][statuses][{{ $status->value }}][points]"
                                                    :value="$existingConfig[$runType->value]['statuses'][$status->value]['points'] ?? 0"
                                                    min="0"
                                                    step="any"
                                                    class="w-24" />
                                            </div>
                                        </div>
                                    </div>
                                @endif
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

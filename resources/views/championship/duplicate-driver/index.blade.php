<x-championship-page-layout :$championship>
    <x-slot name="title">
        {{ __('Duplicate drivers') }} - {{ $championship->title }}
    </x-slot>

    <div class="mb-6 space-y-2 text-sm text-zinc-600 max-w-4xl">
        <p>{{ __('Participations that share the same name or a similar licence number but are not recognized as the same driver. Awards are calculated per driver, so these participations are ranked separately.') }}</p>
        <p>{{ __('Choose the participation with the correct licence and select the ones to merge. The selected participations will receive the licence number of the chosen one.') }}</p>
    </div>

    <x-validation-errors class="mb-4" />

    <p class="mb-4">{{ trans_choice(':count possible duplicate|:count possible duplicates', $groups->count(), ['count' => $groups->count()]) }}</p>

    <div class="space-y-6">
        @forelse ($groups as $group)
            <form
                method="POST"
                action="{{ route('championships.duplicate-drivers.store', $championship) }}"
                class="bg-white shadow-sm ring-1 ring-black/5 md:rounded-lg"
                x-data="{
                    keep: null,
                    merge: [],
                    participations: @js($group['participants']->mapWithKeys(fn ($participant) => [(string) $participant->uuid => ['race' => $participant->race->title, 'licence' => $participant->driver['licence_number'] ?? '']])),
                    choose(uuid) {
                        this.keep = uuid;
                        this.merge = this.merge.filter(id => id !== uuid);
                    },
                    get mergedRaces() {
                        return this.merge.map(id => this.participations[id].race).join(', ');
                    },
                }"
            >
                @csrf

                <div class="flex flex-wrap items-center gap-2 px-4 py-3 border-b border-zinc-200">
                    <h3 class="font-semibold text-zinc-900 mr-2">{{ $group['participants']->first()->full_name }}</h3>

                    @foreach (['same_name' => __('Same name'), 'same_birth_date' => __('Same birth date'), 'same_licence' => __('Similar licence')] as $reason => $label)
                        @if ($group[$reason])
                            <span class="inline-flex items-center rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">{{ $label }}</span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/20 line-through">{{ $label }}</span>
                        @endif
                    @endforeach
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 text-sm">
                        <thead class="bg-zinc-50">
                            <tr class="text-left text-zinc-900">
                                <th scope="col" class="px-3 py-2 font-semibold">{{ __('Keep') }}</th>
                                <th scope="col" class="px-3 py-2 font-semibold">{{ __('Merge') }}</th>
                                <th scope="col" class="px-3 py-2 font-semibold">{{ __('Race') }}</th>
                                <th scope="col" class="px-3 py-2 font-semibold">{{ __('Bib') }}</th>
                                <th scope="col" class="px-3 py-2 font-semibold">{{ __('Category') }}</th>
                                <th scope="col" class="px-3 py-2 font-semibold">{{ __('Driver') }}</th>
                                <th scope="col" class="px-3 py-2 font-semibold">{{ __('Licence') }}</th>
                                <th scope="col" class="px-3 py-2 font-semibold">{{ __('Racer hash') }}</th>
                                <th scope="col" class="px-3 py-2 font-semibold">{{ __('Birth date') }}</th>
                                <th scope="col" class="px-3 py-2 font-semibold">{{ __('Nationality') }}</th>
                                <th scope="col" class="px-3 py-2 font-semibold">{{ __('Fiscal code') }}</th>
                                <th scope="col" class="px-3 py-2 font-semibold">{{ __('E-Mail') }}</th>
                                <th scope="col" class="px-3 py-2 font-semibold">{{ __('Wildcard') }}</th>
                                <th scope="col" class="px-3 py-2 font-semibold">{{ __('Results') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100">
                            @foreach ($group['participants'] as $participant)
                                <tr
                                    class="text-zinc-700 align-top"
                                    :class="{ 'bg-green-50': keep === @js((string) $participant->uuid), 'bg-orange-50': merge.includes(@js((string) $participant->uuid)) }"
                                >
                                    <td class="px-3 py-2">
                                        <input type="radio" name="canonical" value="{{ $participant->uuid }}" required @change="choose(@js((string) $participant->uuid))" class="text-orange-600 focus:ring-orange-500" aria-label="{{ __('Keep licence of :race', ['race' => $participant->race->title]) }}">
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="checkbox" name="duplicates[]" value="{{ $participant->uuid }}" x-model="merge" x-show="keep !== @js((string) $participant->uuid)" class="rounded-sm text-orange-600 focus:ring-orange-500" aria-label="{{ __('Merge participation to :race', ['race' => $participant->race->title]) }}">
                                        <span style="display: none" x-show="keep === @js((string) $participant->uuid)" class="text-xs font-medium text-green-700">{{ __('Kept') }}</span>
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap">
                                        <a class="underline" href="{{ route('participants.show', $participant) }}" target="_blank">{{ $participant->race->title }}</a>
                                        <span class="block text-xs text-zinc-500">{{ $participant->race->period }}</span>
                                    </td>
                                    <td class="px-3 py-2 font-mono">{{ $participant->bib }}</td>
                                    <td class="px-3 py-2">{{ $participant->racingCategory?->name ?? __('no category') }}</td>
                                    <td class="px-3 py-2 whitespace-nowrap">{{ $participant->full_name }}</td>
                                    <td class="px-3 py-2 font-mono font-bold whitespace-nowrap">
                                        <span :class="{ 'line-through text-zinc-400 font-normal': keep && merge.includes(@js((string) $participant->uuid)) }">{{ $participant->driver['licence_number'] ?? '' }}</span>
                                        <span style="display: none" x-show="keep && merge.includes(@js((string) $participant->uuid))" class="block text-green-700" x-text="keep ? participations[keep].licence : ''"></span>
                                    </td>
                                    <td class="px-3 py-2 font-mono">
                                        {{ $participant->racer_hash }}
                                        @if ($participant->racer_hash !== mb_substr($participant->driver_licence, 0, 8))
                                            <span class="block text-xs text-red-600">{{ __('Not matching licence') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap">{{ mb_substr((string) ($participant->driver['birth_date'] ?? ''), 0, 10) }}</td>
                                    <td class="px-3 py-2">{{ $participant->driver['nationality'] ?? '' }}</td>
                                    <td class="px-3 py-2 font-mono">{{ $participant->driver['fiscal_code'] ?? '' }}</td>
                                    <td class="px-3 py-2">{{ $participant->driver['email'] ?? '' }}</td>
                                    <td class="px-3 py-2">{{ $participant->wildcard ? __('Yes') : __('No') }}</td>
                                    <td class="px-3 py-2">{{ $participant->results_count }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-4 px-4 py-3 border-t border-zinc-200 text-sm">
                    <div class="text-zinc-600">
                        <p x-show="!keep">{{ __('Choose in the Keep column the participation with the correct licence.') }}</p>
                        <p style="display: none" x-show="keep">
                            {{ __('Licence to keep') }}:
                            <span class="font-mono font-bold text-zinc-900" x-text="keep ? participations[keep].licence : ''"></span>
                            (<span x-text="keep ? participations[keep].race : ''"></span>)
                        </p>
                        <p style="display: none" x-show="keep && merge.length === 0">{{ __('Select in the Merge column the participations that should receive this licence.') }}</p>
                        <p style="display: none" x-show="keep && merge.length > 0">
                            {{ __('Participations that will receive this licence') }}:
                            <span class="font-medium text-zinc-900" x-text="mergedRaces"></span>
                        </p>
                    </div>

                    {{-- Flux shows a disabled submit button as loading, the button is disabled until the selection is complete --}}
                    <flux:button type="submit" variant="primary" :loading="false" x-bind:disabled="!keep || merge.length === 0">{{ __('Merge selected') }}</flux:button>
                </div>
            </form>
        @empty
            <div class="px-3 py-6 text-center bg-white shadow-sm ring-1 ring-black/5 md:rounded-lg">
                <p>{{ __('No duplicate drivers found.') }}</p>
            </div>
        @endforelse
    </div>

</x-championship-page-layout>

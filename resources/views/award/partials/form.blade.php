@php
    $currentType = $type ?? \App\Models\AwardType::Category;
    $isCategory = $currentType === \App\Models\AwardType::Category;
    $currentAward = $award ?? null;
@endphp

<div class="md:grid md:grid-cols-3 md:gap-6">
    <div class="md:col-span-1">
        <h3 class="text-lg font-medium text-zinc-900">{{ __('Details') }}</h3>
        <p class="mt-1 text-sm text-zinc-600">
            {{ $isCategory ? trans('award.types.category') : trans('award.types.overall') }}
        </p>
    </div>

    <div class="mt-5 md:mt-0 md:col-span-2">
        <div class="px-4 py-5 space-y-6">

            @if($isCategory)
                <div x-data="{ categoryName: '{{ old('name', optional($currentAward)->name ?? '') }}' }">
                    <x-label for="category_id" value="{{ __('Category') }}*" />
                    <select id="category_id" name="category_id" class="mt-1 block w-full border-zinc-300 rounded-md shadow-sm focus:border-orange-300 focus:ring focus:ring-orange-200 focus:ring-opacity-50"
                        x-on:change="if(categoryName === '' || categoryName === $event.target.options[$event.target.selectedIndex - 1]?.text) { categoryName = $event.target.options[$event.target.selectedIndex].text; $refs.nameInput.value = categoryName; }">
                        <option value="">—</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->getKey() }}" @selected(old('category_id', optional($currentAward)->category_id) == $category->getKey())>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="category_id" class="mt-2" />

                    <div class="mt-4">
                        <x-label for="name" value="{{ __('Name') }}*" />
                        <x-input id="name" type="text" name="name" x-ref="nameInput" x-model="categoryName" class="mt-1 block w-full" required />
                        <x-input-error for="name" class="mt-2" />
                    </div>
                </div>
            @else
                <div>
                    <x-label for="name" value="{{ __('Name') }}*" />
                    <x-input id="name" type="text" name="name" :value="old('name', optional($currentAward)->name)" class="mt-1 block w-full" required />
                    <x-input-error for="name" class="mt-2" />
                </div>

                <div>
                    <x-label value="{{ __('Select categories') }}*" />
                    <div class="mt-2 space-y-2">
                        @foreach($categories as $category)
                            <label class="flex items-center">
                                <input type="checkbox" name="category_ids[]" value="{{ $category->getKey() }}"
                                    class="rounded border-zinc-300 text-orange-600 shadow-sm focus:border-orange-300 focus:ring focus:ring-orange-200 focus:ring-opacity-50"
                                    @checked(in_array($category->getKey(), old('category_ids', $currentAward?->categories?->pluck('id')->all() ?? [])))
                                >
                                <span class="ml-2 text-sm text-zinc-600">{{ $category->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error for="category_ids" class="mt-2" />
                </div>
            @endif

        </div>
    </div>
</div>

@if($isCategory)

<x-section-border />

<div class="md:grid md:grid-cols-3 md:gap-6" x-data="{ rankingMode: '{{ old('ranking_mode', optional($currentAward)?->ranking_mode?->value ?? 'all') }}' }">
    <div class="md:col-span-1">
        <h3 class="text-lg font-medium text-zinc-900">{{ __('Ranking mode') }}</h3>
    </div>

    <div class="mt-5 md:mt-0 md:col-span-2">
        <div class="px-4 py-5 space-y-4">

            <div class="space-y-2">
                @foreach(\App\Models\AwardRankingMode::cases() as $mode)
                    <label class="flex items-center">
                        <input type="radio" name="ranking_mode" value="{{ $mode->value }}" x-model="rankingMode"
                            class="border-zinc-300 text-orange-600 shadow-sm focus:border-orange-300 focus:ring focus:ring-orange-200 focus:ring-opacity-50"
                        >
                        <span class="ml-2 text-sm text-zinc-600">{{ $mode->localizedName() }}</span>
                    </label>
                @endforeach
            </div>
            <x-input-error for="ranking_mode" class="mt-2" />

            <div x-show="rankingMode === 'best_n'" x-cloak class="mt-4">
                <x-label for="best_n" value="{{ __('Best N races') }}" />
                <x-input id="best_n" type="number" name="best_n" :value="old('best_n', optional($currentAward)->best_n)" class="mt-1 block w-32" min="1" />
                <x-input-error for="best_n" class="mt-2" />
            </div>

            <div x-show="rankingMode === 'specific'" x-cloak class="mt-4">
                <x-label value="{{ __('Select races') }}" />
                <div class="mt-2 space-y-2">
                    @foreach($races as $race)
                        <label class="flex items-center">
                            <input type="checkbox" name="race_ids[]" value="{{ $race->getKey() }}"
                                class="rounded border-zinc-300 text-orange-600 shadow-sm focus:border-orange-300 focus:ring focus:ring-orange-200 focus:ring-opacity-50"
                                @checked(in_array($race->getKey(), old('race_ids', $currentAward?->races?->pluck('id')->all() ?? [])))
                            >
                            <span class="ml-2 text-sm text-zinc-600">{{ $race->title }}</span>
                        </label>
                    @endforeach
                </div>
                <x-input-error for="race_ids" class="mt-2" />
            </div>

        </div>
    </div>
</div>

<x-section-border />

<div class="md:grid md:grid-cols-3 md:gap-6">
    <div class="md:col-span-1">
        <h3 class="text-lg font-medium text-zinc-900">{{ __('Wildcard filter') }}</h3>
    </div>

    <div class="mt-5 md:mt-0 md:col-span-2">
        <div class="px-4 py-5 space-y-2">
            @foreach(\App\Models\WildcardFilter::cases() as $filter)
                <label class="flex items-center">
                    <input type="radio" name="wildcard_filter" value="{{ $filter->value }}"
                        class="border-zinc-300 text-orange-600 shadow-sm focus:border-orange-300 focus:ring focus:ring-orange-200 focus:ring-opacity-50"
                        @checked(old('wildcard_filter', optional($currentAward)?->wildcard_filter?->value ?? 'all') === $filter->value)
                    >
                    <span class="ml-2 text-sm text-zinc-600">{{ $filter->localizedName() }}</span>
                </label>
            @endforeach
            <x-input-error for="wildcard_filter" class="mt-2" />
        </div>
    </div>
</div>

@endif

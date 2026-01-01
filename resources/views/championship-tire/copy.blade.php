<x-app-layout>
    <x-slot name="title">
        {{ __('Copy tires') }} - {{ $championship->title }}
    </x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 leading-tight flex gap-2">
            <span><a href="{{ route('championships.tire-options.index', $championship) }}">{{ $championship->title }}</a></span>
            <span>/</span>
            <span>{{ __('Copy tires') }}</span>
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('championships.tire-options.store-copy', $championship) }}">
                @csrf

                <div class="md:grid md:grid-cols-3 md:gap-6">
                    <div class="md:col-span-1">
                        <div class="px-4 sm:px-0">
                            <h3 class="text-lg font-medium leading-6 text-zinc-900">{{ __('Copy tires from another championship') }}</h3>
                            <p class="mt-1 text-sm text-zinc-600">
                                {{ __('Select a championship to copy all tires from. The tires will be copied with their names, codes, and prices.') }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-5 md:mt-0 md:col-span-2">
                        <div class="shadow sm:rounded-md sm:overflow-hidden">
                            <div class="px-4 py-5 bg-white space-y-6 sm:p-6">

                                @if($sourceChampionships->isEmpty())
                                    <div class="rounded-md bg-yellow-50 p-4">
                                        <div class="flex">
                                            <div class="ml-3">
                                                <h3 class="text-sm font-medium text-yellow-800">
                                                    {{ __('No championships available') }}
                                                </h3>
                                                <div class="mt-2 text-sm text-yellow-700">
                                                    <p>{{ __('There are no other championships with tires to copy from.') }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div>
                                        <x-label for="source_championship" value="{{ __('Championship') }}" />
                                        <select id="source_championship" name="source_championship" class="mt-1 block w-full rounded-md border-zinc-300 shadow-sm focus:border-orange-500 focus:ring-orange-500" required>
                                            <option value="">{{ __('Select a championship') }}</option>
                                            @foreach($sourceChampionships as $sourceChampionship)
                                                <option value="{{ $sourceChampionship->id }}" {{ old('source_championship') == $sourceChampionship->id ? 'selected' : '' }}>
                                                    {{ $sourceChampionship->title }} ({{ $sourceChampionship->tires_count }} {{ trans_choice('tire|tires', $sourceChampionship->tires_count) }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <x-input-error for="source_championship" class="mt-2" />
                                    </div>
                                @endif

                            </div>

                            @if($sourceChampionships->isNotEmpty())
                                <div class="px-4 py-3 bg-zinc-50 text-right sm:px-6">
                                    <x-button>
                                        {{ __('Copy tires') }}
                                    </x-button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

            </form>
        </div>
    </div>
</x-app-layout>

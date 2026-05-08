<x-app-layout>
    <x-slot name="title">
        {{ __('Import penalty templates') }} - {{ $championship->title }}
    </x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 leading-tight flex gap-2">
            <span><a href="{{ route('championships.penalties.index', $championship) }}">{{ $championship->title }}</a></span>
            <span>/</span>
            <span>{{ __('Import penalty templates') }}</span>
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="px-4 sm:px-6 lg:px-8">

            <form method="POST" action="{{ route('championships.penalties.import.store', $championship) }}">
                @csrf

                <div class="md:grid md:grid-cols-3 md:gap-6">
                    <x-section-title>
                        <x-slot name="title">{{ __('Import multiple penalty templates') }}</x-slot>
                        <x-slot name="description">
                            <p class="mt-1 text-sm text-zinc-600">{{ __('Specify each penalty template on its own line.') }}</p>
                            <p class="mt-1 text-sm text-zinc-600">{{ __('Supported format is:') }}</p>
                            <p class="mt-1 text-sm text-zinc-600"><code>title;description</code></p>
                            <p class="mt-1 text-sm text-zinc-600">{{ __('description is optional.') }}</p>
                            <p class="mt-1 text-sm text-zinc-600">{{ __('For example:') }}</p>
                            <p class="mt-1 text-sm text-zinc-600"><code>False Start;Ten second time penalty for anticipating the start.</code></p>
                            <p class="mt-1 text-sm text-zinc-600"><code>Unsafe driving;</code></p>
                        </x-slot>
                    </x-section-title>

                    <div class="mt-5 md:mt-0 md:col-span-2">
                        <div class="px-4 py-5">
                            <div class="grid grid-cols-6 gap-6">
                                <div class="col-span-6 sm:col-span-4">
                                    <x-label for="penalties" value="{{ __('Penalty templates to import') }}*" />
                                    <x-textarea id="penalties" name="penalties" class="mt-1 block w-full" rows="10" required autofocus>{{ old('penalties') }}</x-textarea>
                                    <x-input-error for="penalties" class="mt-2" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="md:grid md:grid-cols-3 md:gap-6">
                    <div></div>
                    <div class="mt-5 md:mt-0 md:col-span-2">
                        <div class="px-4 py-5">
                            <x-button>
                                {{ __('Import penalty templates') }}
                            </x-button>
                        </div>
                    </div>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>

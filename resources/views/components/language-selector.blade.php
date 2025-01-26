<div>
    
    <div class="ml-3 relative">
        <x-dropdown align="right" width="48">
            <x-slot name="trigger">

                <span class="inline-flex rounded-md">
                    <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-zinc-500 bg-white hover:text-zinc-700 focus:outline-none transition">
                        <span class="inline-block font-mono bg-gray-100 shadow-sm p-1 -my-1">{{ $currentLanguage }}</span>
                        
                        <span class="hidden lg:inline-block ml-2">{{ __('Language') }}</span>

                        <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </span>

            </x-slot>

            <x-slot name="content">

                <x-dropdown-link href="{{ route('language.change', ['lang' => 'it']) }}">
                    {{ __('Italiano') }}
                </x-dropdown-link>
                <x-dropdown-link href="{{ route('language.change', ['lang' => 'en']) }}">
                    {{ __('English') }}
                </x-dropdown-link>


            </x-slot>
        </x-dropdown>
    </div>

    
</div>
<div class="grid grid-cols-1 gap-4 md:grid-cols-2 mb-6">
    <div class="bg-white shadow ring-1 ring-black ring-opacity-5 md:rounded-lg p-4">
        <h3 class="text-lg font-semibold text-zinc-900 mb-4">
            {{ __('First available race numbers') }}
        </h3>

        @if (count($freeNumbers) > 0)
            <p class="text-zinc-900">
                @foreach ($freeNumbers as $number)
                    <span class="font-mono text-lg">{{ $number }}</span>{{ !$loop->last ? ',' : '' }}
                @endforeach
            </p>
        @else
            <p class="text-zinc-500 text-sm">{{ __('No race numbers available within 1 and 999') }}</p>
        @endif
    </div>
    <div class="bg-white shadow ring-1 ring-black ring-opacity-5 md:rounded-lg p-4">

        <h3 class="text-lg font-semibold text-zinc-900 mb-4">
            {{ __('Check a race number') }}
        </h3>

        <form wire:submit="checkAvailability" class="flex gap-3 items-start mb-2">
            <div class="flex-1">
                <x-input
                    type="number"
                    wire:model="checkNumber"
                    placeholder="{{ __('Enter a race number') }}"
                    class="block w-full border-zinc-300 focus:border-orange-500 focus:ring-orange-500 rounded-md shadow-sm"
                    min="1"
                    step="1"
                />
                @error('checkNumber')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <x-button
                type="submit"
            >
                {{ __('Check') }}
            </x-button>

            @if ($checkResult !== null)
                <x-secondary-button
                    type="button"
                    wire:click="clearCheck"
                >
                    {{ __('Clear') }}
                </x-secondary-button>
            @endif
        </form>

        @if ($checkResult === 'available')
            <div class="p-2 bg-green-50 border border-green-300 rounded-md">
                <p class="text-green-800 text-sm">
                    ✓ {{ __('Number :number is available', ['number' => $checkNumber]) }}
                </p>
            </div>
        @elseif ($checkResult === 'taken')
            <div class="p-2 bg-red-50 border border-red-300 rounded-md">
                <p class="text-red-800 text-sm">
                    ✗ {{ __('Number :number is taken by :name', ['number' => $checkNumber, 'name' => $takenBy]) }}
                    @if ($takenByType === 'reservation')
                        <span class="text-red-600">({{ __('Reserved') }})</span>
                    @endif
                </p>
            </div>
        @else
            <div class="p-2 ">
                <p class="text-zinc-800 text-sm">
                    {{ __('Type a number to check if is available.') }}
                    
                </p>
            </div>
        @endif
    </div>
</div>

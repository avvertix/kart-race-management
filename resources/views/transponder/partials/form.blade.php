<form method="POST" action="{{ route('participants.transponders.store', $participant) }}">
    @csrf
    
        <x-jet-section-title>
            <x-slot name="title">{{ __('Assign a transponder') }}</x-slot>
            <x-slot name="description">
                
            </x-slot>
        </x-jet-section-title>

        <x-jet-validation-errors class="mb-4" />

        @for ($i = 0; $i < $transponderLimit; $i++)
            
            <div class="mb-2">
                <x-jet-label for="tire_{{ $i }}" value="{{ __('Transponder :number', ['number' => $i+1]) }}*" />
                <x-jet-input id="tire_{{ $i }}" type="text" name="transponders[]" class="mt-1 block w-full" required autofocus />
            </div>
        @endfor
                  
        <div class="">

            <div class="py-5">
                <x-jet-button class="">
                    {{ __('Assign transponders') }}
                </x-jet-button>
            </div>

        </div>

</form>
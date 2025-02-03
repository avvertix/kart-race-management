@if ($race->hasTotalParticipantLimit())
        
    <div class="max-w-screen-xl mx-auto py-2 px-4 sm:px-6 lg:px-8 shadow-md rounded-md bg-indigo-800 mb-6 flex items-start gap-2 md:gap-4">
        
        <div class="pt-1">
            
            <x-ri-group-3-line class="size-5 text-white" />

        </div>

        <div class="">
            <p class="text-lg font-medium text-white ">{{ __('Limited number competition.') }}</p>

            @if ($race->participants_count && $race->participants_count >= $race->getTotalParticipantLimit())
                <p class="text-lg font-bold text-black px-2 bg-yellow-300 ">{{ __('We reached the maximum allowed participants to this race.') }}</p>
            @endif
            <p class="text-white ">{{ __('In this race we can only accept a maximum of :total participants.', ['total' => $race->getTotalParticipantLimit()]) }}</p>
        </div>
        
    </div>

    @error('participants_limit')
        <x-banner style="danger" :message="$message" />
    @enderror
        
@endif

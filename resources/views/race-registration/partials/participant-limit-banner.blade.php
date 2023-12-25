@if ($race->hasTotalParticipantLimit())
        
    <div class="max-w-screen-xl mx-auto py-2 px-3 sm:px-6 bg-zinc-500 mb-6">
        <div class="flex items-center justify-between flex-wrap">
            <div class="w-0 flex-1 flex items-start min-w-0">
                <div class="flex p-0.5 rounded-lg">
                    
                    <svg class="h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>

                <div class="">
                    <p class="ml-2 text-lg  font-medium text-white truncate">{{ __('Limited number competition.') }}</p>
                    @if ($race->participants_count && $race->participants_count >= $race->getTotalParticipantLimit())
                        <p class="ml-2 text-lg font-bold text-black px-2 bg-yellow-300 truncate">{{ __('We reached the maximum allowed participants to this race.') }}</p>
                    @endif
                    <p class="ml-2 text-white truncate">{{ __('In this race we can only accept a maximum of :total participants.', ['total' => $race->getTotalParticipantLimit()]) }}</p>
                </div>
                    
            </div>

        </div>
    </div>

    @error('participants_limit')
        <x-banner style="danger" :message="$message" />
    @enderror
        
@endif

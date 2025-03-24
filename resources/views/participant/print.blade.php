<x-app-layout>
    <x-slot name="title">
        {{ __('Print participants') }} - {{ $race->title }}
    </x-slot>
    <x-slot name="header">
        @include('race.partials.heading')
    </x-slot>


    <div class="py-6 print:hidden">
        <div class="px-4 sm:px-6 lg:px-8">
            <form action="" id="print_filter" method="get" class=" flex gap-6 items-center lg:justify-between">
                @csrf

                <div class=" flex gap-6 items-center">
                    <div class="">
                        <x-button type="button" class="gap-2" onclick="window.print()">
                            <x-ri-printer-line class="size-4" />
                            {{ __('Print') }}
                        </x-button>
                    </div>
                    <div class="flex gap-2 items-center">
                        <div class="contents">
                            <x-label for="from" class="shrink-0">{{ __('Registered from') }}</x-label>
                            <x-input type="date" name="from" id="from" class="block w-full text-sm" :value="old('from', $from ?? $race->registration_opens_at?->toDateString())" />
                        </div>
                        <div class="contents">
                            <x-label for="to"  class="shrink-0">{{ __('Registered to') }}</x-label>
                            <x-input type="date" name="to" id="to" class="block w-full text-sm" :value="old('to', $to)" />
                        </div>
                        <div class="shrink-0">
                            <x-secondary-button type="submit" class="">
                                {{ __('Apply filter') }}
                            </x-secondary-button>
                        </div>
                    
                    
                    </div>
                </div>

            <div class="self-end flex items-center gap-2">
                    <x-label for="sort" class="sr-only md:not-sr-only shrink-0">{{ __('Sort by') }}</x-label>
                    <select onchange="window.print_filter.submit()" name="sort" id="sort" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="bib" @selected(blank($sort) || $sort === 'bib')>{{ __('Race number') }}</option>
                        <option value="registration" @selected($sort === 'registration')>{{ __('Registration date') }}</option>
                    </select>
                </div>
            
            </form>
        </div>
    </div>


    <div class="py-6 print:py-0">
        <div class="px-4 sm:px-6 lg:px-8 print:px-0">

            @foreach ($participants as $participant)
                           
                    <div class="p-4 shadow-lg bg-white rounded-md mb-6 print:shadow-none print:break-inside-avoid">
                        <div class="space-y-1 mb-3">
                            <p class="hidden print:block">{{ $race->title }}</p>
                            <h3 class="text-3xl font-bold flex items-center gap-2">
                                <span class="font-mono px-2 py-1 rounded bg-orange-100 text-orange-700 print:bg-orange-100">{{ $participant->bib }}</span>
                                <span>{{ $participant->first_name }} {{ $participant->last_name }}</span>
                            </h3>
                            <p class="text-xl">{{ $participant->racingCategory?->name ?? __('no category') }} / {{ $participant->engine }}</p>
                            @if ($participant->racingCategory?->tire)
                                <p class="text-xl">{{ $participant->racingCategory?->tire->name }}</p>
                            @endif
                        </div>

                        <div class="space-y-2">

                            @if(auth()->user()->hasPermission('payment:view'))
                                <div class="flex gap-2 items-baseline">
                                    @if ($participant->use_bonus)
                                        <span class="text-sm bg-indigo-100 text-indigo-700 px-2 py-1 rounded">{{ __('Bonus')}}</span>
                                    @endif

                                    {{ $participant->payment_channel?->localizedName() }}
                                </div>
                            @endif
                            
                            <div class="">
                                <p class="font-bold ">{{ __('Driver') }}</p>
                                <p>
                                    {{ $participant->first_name }}
                                    {{ $participant->last_name }}
                                </p>
                                <p>
                                    {{ $participant->driver['nationality'] }}
                                    {{ $participant->licence_type?->localizedName() }}
                                    <span class="font-mono font-bold">{{ $participant->driver['licence_number'] }}</span>
                                </p>
                                <p>
                                    {{ $participant->driver['email'] }}
                                    {{ $participant->driver['phone'] }}
                                </p>
                                <p>
                                    {{ __('Birth :place on :date', [
                                        'place' => $participant->driver['birth_place'],
                                        'date' => $participant->driver['birth_date'],
                                    ]) }}
                                </p>
                                <p>
                                    {{ __('Medical certificate expires on :date', [
                                        'date' => $participant->driver['medical_certificate_expiration_date'],
                                    ]) }}
                                </p>
                                <p>
                                    {{ __('Residence in :address, :city :province :postal_code', [
                                        'address' => $participant->driver['residence_address']['address'] ?? null,
                                        'city' => $participant->driver['residence_address']['city'] ?? null,
                                        'postal_code' => $participant->driver['residence_address']['postal_code'] ?? null,
                                        'province' => $participant->driver['residence_address']['province'] ?? null,
                                    ]) }}
                                </p>
                            </div>
                            <div class="">
                                <p class="font-bold ">{{ __('Competitor') }}</p>
                                @if ($participant->competitor)
                                    <p class="">
                                        {{ $participant->competitor['first_name'] }}
                                        {{ $participant->competitor['last_name'] }}
                                    </p>
                                    <p class="">
                                        {{ $participant->competitor['nationality'] }}
                                        {{ \App\Models\CompetitorLicence::from($participant->competitor['licence_type'])?->localizedName() }}
                                        <span class="font-mono font-bold">{{ $participant->competitor['licence_number'] }}</span>
                                    </p>
                                    
                                    <p>
                                        {{ $participant->competitor['email'] }}
                                        {{ $participant->competitor['phone'] }}
                                    </p>
                                    <p>
                                        {{ __('Birth :place on :date', [
                                            'place' => $participant->competitor['birth_place'],
                                            'date' => $participant->competitor['birth_date'],
                                        ]) }}
                                    </p>
                                    <p>
                                        {{ __('Residence in :address, :city :province :postal_code', [
                                            'address' => $participant->competitor['residence_address']['address'] ?? null,
                                            'city' => $participant->competitor['residence_address']['city'] ?? null,
                                            'postal_code' => $participant->competitor['residence_address']['postal_code'] ?? null,
                                            'province' => $participant->competitor['residence_address']['province'] ?? null,
                                        ]) }}
                                    </p>
                                @else
                                    <p>{{ __('No competitor specified') }}</p>
                                @endif
                            </div>

                            <div class="">
                                <p class="font-bold">{{ __('Mechanic') }}</p>
                                @if ($participant->mechanic)
                                    <p>
                                        {{ $participant->mechanic['name'] }}
                                        <span class="font-mono font-bold">{{ $participant->mechanic['licence_number'] }}</span>
                                    </p>
                                @else
                                    <p>{{ __('No mechanic specified') }}</p>
                                @endif
                            </div>
                            <div class="">
                                <p class="font-bold col-span-2">{{ __('Vehicle') }}</p>

                                @foreach ($participant->vehicles as $vehicle)
                                    <div class="space-y-1">
                                        <div class="">
                                            <span class="text-sm text-zinc-500 block">{{ __('Chassis') }}</span>
                                            {{ $vehicle['chassis_manufacturer'] }}
                                        </div>
                                        <div class="">
                                            <span class="text-sm text-zinc-500 block">{{ __('Engine') }}</span>
                                            {{ $vehicle['engine_manufacturer'] }}
                                            {{ $vehicle['engine_model'] }}
                                        </div>
                                        <div class="col-span-2">
                                            <span class="text-sm text-zinc-500 block">{{ __('Oil') }}</span>
                                            {{ $vehicle['oil_manufacturer'] }}
                                            {{ $vehicle['oil_type'] }}
                                            {{ $vehicle['oil_percentage'] }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
        
                
            @endforeach
                
        </div>
    </div>
</x-app-layout>

<x-app-layout>
    <x-slot name="header">
        @include('race.partials.heading')
    </x-slot>


    <div class="py-6 print:py-0">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 print:px-0">

            @foreach ($participants as $participant)
                           
                    <div class="p-4 shadow-lg bg-white rounded-md mb-6 print:shadow-none flex print:break-inside-avoid">
        
                        <div class="">
                            <h3 class="text-3xl font-bold flex items-center gap-2">
                                <span class="font-mono px-2 py-1 rounded bg-orange-100 text-orange-700 print:bg-orange-100">{{ $participant->bib }}</span>
                                <span>{{ $participant->first_name }} {{ $participant->last_name }}</span>
                            </h3>
                            <p class="mb-1 text-xl">{{ $participant->category()->name }} / {{ $participant->engine }}</p>
                            <p class="mb-6 text-xl">{{ $participant->tire()->name }}</p>
                            
                            <div class="mb-2">
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
                            <div class="mb-2">
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

                            <div class="mb-2">
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

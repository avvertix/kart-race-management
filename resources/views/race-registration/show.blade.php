<x-app-layout>
    <x-slot name="header">
        <div class="relative pb-5 sm:pb-0 print:hidden">
            <div class="md:flex md:items-center md:justify-between">
                <h2 class="font-semibold text-4xl text-zinc-800 leading-tight">
                    {{ __('Race participation') }}
                </h2>
                <div class="mt-3 flex md:absolute md:top-3 md:right-0 md:mt-0 gap-2">

                </div>
            </div>
            <div class="prose prose-zinc">
                <p class="font-bold">{{ __('You must present yourself to the secretary on the race track before the closing of the registrations to confirm your participation.') }}</p>
                <p>{{ __('Please bring this receipt with you to the race (printed version or PDF).') }}</p>
            </div>
            
        </div>

    </x-slot>

    <div class="py-3 print:hidden bg-white border-y border-zinc-300">
        

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <h3 class="text-xl font-bold mb-1">{{ __('Race participation price') }} <x-price class="font-mono">{{ $participant->price()->last() }}</x-price></h3>

            <div class="grid lg:gap-4 lg:grid-cols-2">

                <div class="prose prose-zinc">
                    <p>{{ __('Race cost is calculated from a fixed fee plus one tire set, based on the selected category.') }}</p>
                    <table>
                        @foreach ($participant->price() as $key => $price)
                            <tr>
                                <td class="{{ $loop->last ? 'font-bold' : ''}}">{{ $key }}</td>
                                <td class="text-right {{ $loop->last ? 'font-bold' : ''}}"><x-price>{{ $price }}</x-price></td>
                                <td class="min-w-[40px]">&nbsp;</td>
                            </tr>
                        @endforeach
                    </table>
                </div>

                <div class="prose prose-zinc">
                    <p>{{ __('Race participation can be paid via bank transfer to') }}</p>
                    <p class="bg-zinc-50 p-2 shadow">{{ config('races.organizer.name') }}
                        <br>{{ config('races.organizer.bank') }}
                        <br><span class="font-mono">{{ config('races.organizer.bank_account') }}</span>
                    </p>
                    <p>{{ __('Once paid send the bank transfer receipt to :email', ['email' => config('races.organizer.email')]) }}</p>
                </div>
            </div>
        </div>
        
    </div>


    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="">
                <h3 class="text-xl font-bold mb-1">{{ $race->title }}</h3>
                <p class="text-base">{{ $championship->title }}</p>
                <p class="text-zinc-700 mb-1">{{ $race->period }} / {{ $race->track }}</p>
            </div>
            
            <div class="p-4 -mx-4 shadow-lg bg-white rounded-md mb-6 print:shadow-none flex">

                <div class="lg:w-2/3">
                    <h3 class="text-3xl font-bold flex items-center gap-2">
                        <span class="font-mono px-2 py-1 rounded bg-orange-100 text-orange-700 print:bg-orange-100">{{ $participant->bib }}</span>
                        <span>{{ $participant->first_name }} {{ $participant->last_name }}</span>
                    </h3>
                    <p class="mb-1 text-xl">{{ $participant->category()->name }} / {{ $participant->engine }}</p>
                    <p class="mb-6 text-xl">{{ $participant->tire()->name }}</p>
                    
                    <div class="grid md:grid-cols-2 mb-2">
                        <p class="font-bold md:col-span-2">{{ __('Driver') }}</p>
                        <p>
                            {{ $participant->first_name }}
                            {{ $participant->last_name }}
                        </p>
                        <p>
                            {{ $participant->driver['nationality'] }}
                            {{ $participant->licence_type?->name }}
                            {{ $participant->driver['licence_number'] }}
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
                    <div class="grid md:grid-cols-2">
                        <div class="grid md:grid-cols-3 mb-2">
                            <p class="font-bold md:col-span-3">{{ __('Competitor') }}</p>
                            @if ($participant->competitor)
                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="competitor_first_name" value="{{ __('Name') }}*" />
                                    {{ $participant->competitor['first_name'] }}
                                </div>
                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="competitor_last_name" value="{{ __('Surname') }}*" />
                                    {{ $participant->competitor['last_name'] }}
                                </div>
                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="competitor_licence_type" value="{{ __('Licence Type') }}*" />
                                    {{ $participant->competitor['licence_type'] }}
                                </div>
                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="competitor_licence_number" value="{{ __('Licence Number') }}*" />
                                    {{ $participant->competitor['licence_number'] }}
                                </div>
                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="competitor_nationality" value="{{ __('Nationality') }}*" />
                                    {{ $participant->competitor['nationality'] }}
                                </div>
                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="competitor_email" value="{{ __('E-Mail') }}*" />
                                    {{ $participant->competitor['email'] }}
                                </div>
                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="competitor_phone" value="{{ __('Phone number') }}*" />
                                    {{ $participant->competitor['phone'] }}
                                </div>
                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="competitor_birth_date" value="{{ __('Birth date') }}*" />
                                    {{ $participant->competitor['birth_date'] }}
                                </div>
                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="competitor_birth_place" value="{{ __('Birth place') }}*" />
                                    {{ $participant->competitor['birth_place'] }}
                                </div>
                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="competitor_residence" value="{{ __('Residence address') }}*" />
                                    {{ $participant->competitor['residence_address']['address'] ?? null }}
                                    {{ $participant->competitor['residence_address']['city'] ?? null }}
                                    {{ $participant->competitor['residence_address']['postal_code'] ?? null }}
                                    {{ $participant->competitor['residence_address']['province'] ?? null }}
                                </div>
                            @else
                                <p>{{ __('No competitor specified') }}</p>
                            @endif
                        </div>
                        <div class="mb-2">
                            <p class="font-bold">{{ __('Mechanic') }}</p>
                            @if ($participant->mechanic)
                                <p>
                                    {{ $participant->mechanic['name'] }}
                                    {{ $participant->mechanic['licence_number'] }}
                                </p>
                            @else
                                <p>{{ __('No mechanic specified') }}</p>
                            @endif
                        </div>
                    </div>
                    <div class="mt-6 grid md:grid-cols-2">
                    
                        @foreach ($participant->vehicles as $vehicle)
                            <div class="grid sm:grid-cols-2 gap-1">
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
                <div class="hidden lg:w-1/3 lg:flex justify-end p-4">
                    {!! $participant->qrCodeSvg() !!}
                </div>
            </div>

            <div class="hidden mt-6 print:block space-y-2">
                <p>{{ __('Participation detail') }}</p>
                <p class="font-mono text-lg">{{ $participant->id }} / {{ $participant->uuid }}</p>
                <p>{{ $participant->created_at }}</p>
            </div>
            
        </div>
    </div>
</x-app-layout>


<x-section-border />

<div class="md:grid md:grid-cols-3 md:gap-6">
    <x-section-title>
        <x-slot name="title">{{ __('Participation price') }}</x-slot>
        <x-slot name="description">{{ __('The expected price to pay to participate to the race.') }}</x-slot>
    </x-section-title>

    <div class="mt-5 md:mt-0 md:col-span-2">

            <div class="px-4 py-5">
                <div class="grid grid-cols-6 gap-6">
                    <div class="col-span-6 sm:col-span-4 prose prose-zinc">
                        <p>{{ __('Race cost is calculated from a fixed fee plus one tire set, based on the selected category.') }}</p>
                        <p>{{ __('Here is the price list. The final price is given after submitting the registration.') }}</p>
                        <table>
                            <tr>
                                <td class="font-bold">{{ __('Race fee') }}</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>{{ __('Participation fee') }}</td>
                                <td><x-price>{{ config('races.price') }}</x-price></td>
                            </tr>
                            <tr>
                                <td class="font-bold">{{ __('Tires') }}</td>
                                <td></td>
                            </tr>
                            @foreach (\App\Models\TireOption::all() as $tire)
                                <tr>
                                    <td>{{ $tire->name }}</td>
                                    <td><x-price>{{ $tire->price }}</x-price></td>
                                </tr>
                            @endforeach
                        </table>
                    </div>
                </div>
            </div>
    </div>
</div>
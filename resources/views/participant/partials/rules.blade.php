<x-jet-section-border />
            
<div class="md:grid md:grid-cols-3 md:gap-6">
    <x-jet-section-title>
        <x-slot name="title">{{ __('Rules') }}</x-slot>
        <x-slot name="description">{{ __('Here some regulation remarks.') }}</x-slot>
    </x-jet-section-title>

    <div class="mt-5 md:mt-0 md:col-span-2">

            <div class="px-4 py-5">
                <div class="grid grid-cols-6 gap-6">
                    <div class="col-span-6 sm:col-span-4 prose prose-zinc">
                        <p>{{ __('For allowed tires please refer to the category regulation.') }}</p>

                        <p>{{ __('Hi hereby declare:') }}</p>

                        <ul>
                            <li>{{ __('that I\'m aware of the disciplinary sanctions in case of wrong declarations in this registration form') }}</li>
                            <li>{{ __('to participate or read the Briefing') }}</li>
                            <li>{{ __('to make use of the assistance of an ACI Sport licenced mechanic') }}</li>
                            <li>{{ __('to have read and accepted the ACI Sport Karting 2023 regulations') }}</li>
                            <li>{{ __('to reimburse 200,00 Euro to the time keeping service in case of loss of the assigned transponder') }}</li>
                        </ul>

                    
                        <p><strong>{{ __('The confirmation of acceptance will be done by sending an email to both the driver and the competitor. The email will contain a link to confirm.') }}</strong></p>

                    </div>
                </div>
            </div>
    </div>
</div>
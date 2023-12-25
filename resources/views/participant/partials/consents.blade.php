<x-section-border />
            
<div class="md:grid md:grid-cols-3 md:gap-6">
    <x-section-title>
        <x-slot name="title">{{ __('Consents') }}</x-slot>
        <x-slot name="description">{{ __('Privacy is important to us.') }}</x-slot>
    </x-section-title>

    <div class="mt-5 md:mt-0 md:col-span-2">

            <div class="px-4 py-5">
                <div class="grid grid-cols-6 gap-6">
                    <div class="col-span-6 sm:col-span-4">
                        <x-label for="consent_privacy" class="text-base">
                            <div class="flex items-center">
                                <x-checkbox name="consent_privacy" id="consent_privacy" required />
    
                                <div class="ml-2">
                                    {!! __('I agree to the :privacy_policy', [
                                            'privacy_policy' => '<a target="_blank" href="'.route('policy.show').'" class="underline text-zinc-600 hover:text-zinc-900">'.__('Privacy Policy').'</a>',
                                    ]) !!}
                                </div>
                            </div>
                        </x-label>
                    </div>
                </div>
            </div>
    </div>
</div>
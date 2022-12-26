<div class="-my-2 -mx-4 overflow-x-auto sm:-mx-6 lg:-mx-8">
    <div class="inline-block min-w-full py-2 align-middle md:px-6 lg:px-8">
        <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
            <table class="min-w-full divide-y divide-zinc-300">
                @isset($head)
                    <thead {{ $head->attributes->class(['bg-zinc-50']) }}">
                        <tr>
                            {{ $head }}
                        </tr>
                    </thead>
                @endisset
                <tbody class="divide-y divide-zinc-200 bg-white">
                    {{ $slot }}
                </tbody>
            </table>
        </div>
    </div>
</div>
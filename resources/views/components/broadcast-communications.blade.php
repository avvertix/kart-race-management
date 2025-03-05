<div {{ $attributes }}>
    @foreach ($communications as $communication)
        <div class="bg-yellow-200 text-black py-2 px-4 sm:px-6 lg:px-8 print:hidden">
            <div class=" prose prose-p:text-black">
                {{ $communication }}
            </div>
        </div>
    @endforeach
</div>
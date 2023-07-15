<div {{ $attributes }}>
    @foreach ($communications as $communication)
        <div class="bg-yellow-200 text-black py-2 px-4 md:px-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 prose prose-p:text-black">
                {{ $communication }}
            </div>
        </div>
    @endforeach
</div>
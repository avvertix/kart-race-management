<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-zinc-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-zinc-700 active:bg-zinc-900 focus:outline-none focus:border-zinc-900 focus:ring focus:ring-zinc-300 disabled:opacity-25 transition']) }}>
    {{ $slot }}
</button>

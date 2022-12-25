<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-4 py-2 bg-white border border-zinc-300 rounded-md font-semibold text-xs text-zinc-700 uppercase tracking-widest shadow-sm hover:text-zinc-500 focus:outline-none focus:border-blue-300 focus:ring focus:ring-blue-200 active:text-zinc-800 active:bg-zinc-50 disabled:opacity-25 transition']) }}>
    {{ $slot }}
</button>

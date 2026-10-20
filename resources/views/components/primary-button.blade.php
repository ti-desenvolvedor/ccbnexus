<button {{ $attributes->merge([
    'type' => 'submit',
    'class' => 'inline-flex items-center justify-center rounded-xl border border-transparent bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 active:bg-primary-800 disabled:opacity-50 dark:focus:ring-offset-slate-950',
]) }}>
    {{ $slot }}
</button>

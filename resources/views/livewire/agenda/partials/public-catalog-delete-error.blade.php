@if ($deleteError ?? null)
    <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-900 dark:bg-rose-950/50 dark:text-rose-200" role="alert">
        {{ $deleteError }}
    </div>
@endif

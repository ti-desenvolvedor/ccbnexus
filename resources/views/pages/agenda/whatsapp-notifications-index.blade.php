<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <div class="text-xs font-semibold uppercase tracking-wide text-primary-700 dark:text-primary-300">Agenda</div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">Notificações WhatsApp</h1>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Gere, edite, copie e marque como enviado manualmente.</p>
            </div>
            <a href="{{ route('agenda.whatsapp-templates.index') }}" wire:navigate><x-ui.button variant="secondary">Templates</x-ui.button></a>
        </div>
    </x-slot>

    <livewire:agenda.whats-app-notifications-center />
</x-app-layout>


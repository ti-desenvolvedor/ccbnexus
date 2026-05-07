<div>
    @if (session('status'))
        <div class="mb-4 rounded-md bg-green-50 p-3 text-sm text-green-800">{{ session('status') }}</div>
    @endif
    <form wire:submit="submit" class="space-y-4">
        <div>
            <x-input-label for="name" value="Nome" />
            <x-text-input wire:model="name" id="name" class="mt-1 block w-full" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input wire:model="email" id="email" type="email" class="mt-1 block w-full" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="phone" value="Telefone (opcional)" />
            <x-text-input wire:model="phone" id="phone" class="mt-1 block w-full" />
        </div>
        <div>
            <x-input-label for="message" value="Mensagem" />
            <textarea wire:model="message" id="message" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
        </div>
        <x-primary-button type="submit">Enviar pedido</x-primary-button>
    </form>
</div>

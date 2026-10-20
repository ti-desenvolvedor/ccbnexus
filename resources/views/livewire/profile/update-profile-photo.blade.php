<div
    class="flex flex-col items-center gap-4 lg:items-start"
    x-data="profilePhotoCrop(@js([
        'viewSize' => 288,
        'outputSize' => 512,
        'savingLabel' => __('A guardar…'),
        'errorLabel' => __('Não foi possível processar a imagem.'),
    ]))"
    @profile-photo-updated.window="onSaved()"
>
    <div class="relative" wire:loading.class="opacity-60" wire:target="photo,savePhoto,removePhoto">
        <x-user.avatar
            :user="$user"
            size="2xl"
            class="!h-32 !w-32 rounded-full ring-4 ring-white/90 dark:ring-slate-800"
            wire:key="avatar-{{ $user?->profile_photo_path ?? 'none' }}-{{ $user?->updated_at?->timestamp }}"
        />

        <label
            for="profile-photo-upload"
            class="absolute bottom-0 right-0 flex h-11 w-11 cursor-pointer items-center justify-center rounded-full border-2 border-white bg-white text-primary-700 shadow-lg transition hover:bg-primary-50"
            title="{{ __('Personalizar foto') }}"
        >
            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 5.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 0 0-1.134-.175 2.31 2.31 0 0 1-1.64-1.055l-.822-1.316a2.192 2.192 0 0 0-1.736-1.039 48.774 48.774 0 0 0-5.232 0 2.192 2.192 0 0 0-1.736 1.039l-.821 1.316Z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Z" />
            </svg>
            <input
                id="profile-photo-upload"
                type="file"
                class="sr-only"
                accept="image/jpeg,image/png,image/webp"
                x-ref="fileInput"
                x-on:change="openFromFile($event)"
            />
        </label>
    </div>

    <p class="text-center text-sm font-medium text-white lg:text-left">{{ __('Personalizar foto') }}</p>

    <div wire:loading wire:target="photo,savePhoto" class="text-xs text-primary-100/80">
        {{ __('A carregar…') }}
    </div>

    @if (session('photo_status'))
        <p class="text-sm font-medium text-emerald-200" role="status">{{ session('photo_status') }}</p>
    @endif

    <x-input-error class="text-amber-200" :messages="$errors->get('photo')" />
    <p x-show="error" x-text="error" x-cloak class="max-w-sm text-center text-sm text-amber-200 lg:text-left"></p>

    <div class="flex max-w-sm flex-col gap-2 text-center text-sm lg:text-left">
        <p class="text-primary-100/80">
            {{ __('JPG, PNG ou WebP. Pode ajustar o enquadramento antes de guardar.') }}
        </p>
        @if ($user?->hasProfilePhoto())
            <button
                type="button"
                wire:click="removePhoto"
                wire:confirm="{{ __('Remover a foto de perfil?') }}"
                class="font-semibold text-red-200 hover:text-white"
            >
                {{ __('Remover foto') }}
            </button>
        @endif
    </div>

    {{-- Editor de crop --}}
    <div
        x-show="open"
        x-cloak
        class="fixed inset-0 z-[80] flex items-end justify-center bg-slate-950/70 p-4 sm:items-center"
        x-on:keydown.escape.window="close()"
    >
        <div
            class="absolute inset-0"
            x-on:click="close()"
            aria-hidden="true"
        ></div>

        <div
            class="relative z-10 w-full max-w-lg overflow-hidden rounded-3xl border border-white/10 bg-slate-900 text-white shadow-2xl"
            role="dialog"
            aria-modal="true"
            aria-labelledby="profile-photo-crop-title"
            x-on:click.stop
        >
            <div class="border-b border-white/10 px-5 py-4">
                <h3 id="profile-photo-crop-title" class="text-lg font-semibold tracking-tight">{{ __('Ajustar foto de perfil') }}</h3>
                <p class="mt-1 text-sm text-slate-300">{{ __('Arraste para posicionar e use o zoom. A pré-visualização mostra o resultado final.') }}</p>
            </div>

            <div class="space-y-5 px-5 py-5">
                <div class="flex flex-col items-center gap-5 sm:flex-row sm:items-start sm:justify-center">
                    <div
                        class="relative touch-none overflow-hidden rounded-full bg-slate-800 shadow-inner ring-4 ring-white/20"
                        x-bind:style="`width:${viewSize}px;height:${viewSize}px`"
                        x-on:pointerdown="startDrag($event)"
                        x-on:pointermove="onDrag($event)"
                        x-on:pointerup="endDrag()"
                        x-on:pointercancel="endDrag()"
                        x-on:wheel.prevent="onWheel($event)"
                    >
                        <img
                            x-show="src"
                            x-bind:src="src"
                            alt=""
                            draggable="false"
                            class="pointer-events-none absolute left-0 top-0 max-w-none select-none will-change-transform"
                            x-bind:style="imageStyle()"
                        />
                        <div class="pointer-events-none absolute inset-0 rounded-full ring-1 ring-inset ring-white/30"></div>
                    </div>

                    <div class="flex flex-col items-center gap-3 sm:pt-2">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-400">{{ __('Pré-visualização') }}</p>
                        <div class="relative h-24 w-24 overflow-hidden rounded-full bg-slate-800 ring-2 ring-white/25">
                            <img
                                x-show="src"
                                x-bind:src="src"
                                alt=""
                                draggable="false"
                                class="pointer-events-none absolute left-0 top-0 max-w-none select-none"
                                x-bind:style="previewStyle()"
                            />
                        </div>
                        <p class="max-w-[9rem] text-center text-xs text-slate-400">{{ __('Assim ficará no perfil') }}</p>
                    </div>
                </div>

                <div class="space-y-2">
                    <div class="flex items-center justify-between text-xs text-slate-300">
                        <span>{{ __('Zoom') }}</span>
                        <span x-text="Math.round((scale / minScale) * 100) + '%'"></span>
                    </div>
                    <input
                        type="range"
                        min="100"
                        max="300"
                        step="1"
                        class="w-full accent-primary-400"
                        x-bind:value="Math.round((scale / minScale) * 100)"
                        x-on:input="setZoomPercent($event.target.value)"
                    />
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-2 border-t border-white/10 bg-slate-950/40 px-5 py-4">
                <button
                    type="button"
                    class="rounded-xl border border-white/20 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10 disabled:opacity-50"
                    x-on:click="close()"
                    x-bind:disabled="saving"
                >
                    {{ __('Cancelar') }}
                </button>
                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-xl bg-white px-4 py-2 text-sm font-semibold text-primary-800 hover:bg-primary-50 disabled:opacity-50"
                    x-on:click="applyAndSave()"
                    x-bind:disabled="saving || !src"
                >
                    <span x-show="!saving">{{ __('Guardar foto') }}</span>
                    <span x-show="saving" x-text="savingLabel"></span>
                </button>
            </div>
        </div>
    </div>
</div>

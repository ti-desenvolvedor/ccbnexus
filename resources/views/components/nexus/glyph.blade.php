@props(['name' => 'info'])

<svg {{ $attributes->merge(['class' => 'shrink-0']) }} width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
    @switch($name)
        @case('check')
            <circle cx="12" cy="12" r="8" stroke-width="2" />
            <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M8.5 12.5l2.2 2.2 4.8-5" />
            @break
        @case('alert')
            <path stroke-width="2" stroke-linejoin="round" d="M12 3.5 20.5 18.5h-17z" />
            <path stroke-width="2" stroke-linecap="round" d="M12 9v4" />
            <circle cx="12" cy="16.2" r="0.8" fill="currentColor" stroke="none" />
            @break
        @case('danger')
            <circle cx="12" cy="12" r="8" stroke-width="2" />
            <path stroke-width="2" stroke-linecap="round" d="M12 8v5" />
            <circle cx="12" cy="16.2" r="0.8" fill="currentColor" stroke="none" />
            @break
        @case('mail')
            <rect x="3.5" y="5.5" width="17" height="13" rx="2" stroke-width="2" />
            <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M4 7l8 6 8-6" />
            @break
        @case('trash')
            <path stroke-width="2" stroke-linecap="round" d="M5 7h14" />
            <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M9 7V5h6v2M7 7l1 13h8l1-13" />
            @break
        @case('ban')
            <circle cx="12" cy="12" r="8" stroke-width="2" />
            <path stroke-width="2" stroke-linecap="round" d="M7.2 7.2 16.8 16.8" />
            @break
        @case('reject')
            <circle cx="12" cy="12" r="8" stroke-width="2" />
            <path stroke-width="2" stroke-linecap="round" d="M9 9l6 6M15 9l-6 6" />
            @break
        @case('refresh')
            <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M20 12a8 8 0 0 0-13.7-5.6L4 8" />
            <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M4 4v4h4" />
            <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M4 12a8 8 0 0 0 13.7 5.6L20 16" />
            <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M20 20v-4h-4" />
            @break
        @case('help')
            <circle cx="12" cy="12" r="8" stroke-width="2" />
            <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M9.5 9.5a2.5 2.5 0 1 1 4 2c-.8.6-1.5 1.1-1.5 2.2" />
            <circle cx="12" cy="16.6" r="0.8" fill="currentColor" stroke="none" />
            @break
        @case('photo')
            <rect x="4" y="5" width="16" height="14" rx="2" stroke-width="2" />
            <circle cx="9" cy="10" r="1.3" stroke-width="2" />
            <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M4 16.5 8.2 12l3 3 2.2-2.2L20 16.5" />
            <path stroke-width="2" stroke-linecap="round" d="M5 19 19 5" />
            @break
        @case('calendar')
            <rect x="4" y="5" width="16" height="15" rx="2" stroke-width="2" />
            <path stroke-width="2" stroke-linecap="round" d="M8 3.5v4M16 3.5v4M4 10h16" />
            @break
        @case('undo')
            <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M3 12a9 9 0 1 0 9-9" />
            <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M3 4v5h5" />
            @break
        @case('person')
            <circle cx="12" cy="8" r="3" stroke-width="2" />
            <path stroke-width="2" stroke-linecap="round" d="M6 19.2c.9-3 3-4.5 6-4.5s5.1 1.5 6 4.5" />
            @break
        @case('eye')
            <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M2.5 12S6.5 6.5 12 6.5 21.5 12 21.5 12 17.5 17.5 12 17.5 2.5 12 2.5 12Z" />
            <circle cx="12" cy="12" r="2.25" stroke-width="2" />
            @break
        @default
            <circle cx="12" cy="12" r="8" stroke-width="2" />
            <path stroke-width="2" stroke-linecap="round" d="M12 11v5" />
            <circle cx="12" cy="8" r="0.8" fill="currentColor" stroke="none" />
    @endswitch
</svg>

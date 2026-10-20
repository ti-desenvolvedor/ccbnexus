<?php

namespace App\Livewire\Profile;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class UpdateAccountPreferences extends Component
{
    public int $list_per_page = 20;

    public string $ui_theme = 'light';

    public string $ui_palette = 'blue';

    public function mount(): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $this->list_per_page = $user->resolvedListPerPage();
        $this->ui_theme = $user->resolvedUiTheme();
        $this->ui_palette = $user->resolvedUiPalette();
    }

    public function setListPerPage(int $value): void
    {
        $this->list_per_page = $value;
    }

    public function save(): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $min = (int) config('nexus.pagination.per_page_min', 5);
        $max = (int) config('nexus.pagination.per_page_max', 200);
        $allowedThemes = config('nexus.ui.themes', ['light', 'dark']);
        $allowedPalettes = config('nexus.ui.palettes', ['blue']);

        $validated = $this->validate([
            'list_per_page' => ['required', 'integer', "min:{$min}", "max:{$max}"],
            'ui_theme' => ['required', 'string', 'in:'.implode(',', $allowedThemes)],
            'ui_palette' => ['required', 'string', 'in:'.implode(',', $allowedPalettes)],
        ]);

        $user->fill($validated);
        $user->save();

        $this->dispatch('account-preferences-updated', theme: $this->ui_theme, palette: $this->ui_palette);
        session()->flash('preferences_status', __('Preferências guardadas.'));
    }

    public function isPresetPerPage(int $value): bool
    {
        return in_array($value, $this->perPageOptions, true);
    }

    /**
     * @return array<int, int>
     */
    public function getPerPageOptionsProperty(): array
    {
        return config('nexus.pagination.per_page_options', [10, 20, 50, 100]);
    }

    public function getPerPageMinProperty(): int
    {
        return (int) config('nexus.pagination.per_page_min', 5);
    }

    public function getPerPageMaxProperty(): int
    {
        return (int) config('nexus.pagination.per_page_max', 200);
    }

    /**
     * @return array<int, string>
     */
    public function getThemeOptionsProperty(): array
    {
        return config('nexus.ui.themes', ['light', 'dark']);
    }

    /**
     * @return array<int, string>
     */
    public function getPaletteOptionsProperty(): array
    {
        return config('nexus.ui.palettes', ['blue']);
    }

    public function render()
    {
        return view('livewire.profile.update-account-preferences');
    }
}

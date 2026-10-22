<?php

namespace App\Models;

use App\Support\PasswordStrength;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens;
    use HasFactory;
    use HasRoles;
    use LogsActivity;
    use Notifiable;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'password_strength',
        'phone',
        'profile_photo_path',
        'google_id',
        'regional_id',
        'administration_id',
        'prayer_house_id',
        'list_per_page',
        'ui_theme',
        'ui_palette',
        'ui_sidebar',
    ];

    /**
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_super_admin' => 'boolean',
    ];

    public function regional(): BelongsTo
    {
        return $this->belongsTo(Regional::class);
    }

    public function administration(): BelongsTo
    {
        return $this->belongsTo(Administration::class);
    }

    public function prayerHouse(): BelongsTo
    {
        return $this->belongsTo(PrayerHouse::class);
    }

    public function passwordStrengthLabel(): string
    {
        return PasswordStrength::label($this->password_strength);
    }

    public function passwordStrengthTone(): string
    {
        return PasswordStrength::tone($this->password_strength);
    }

    public function passwordNeedsChange(): bool
    {
        return PasswordStrength::needsChange($this->password_strength);
    }

    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name', 'email', 'phone', 'password_strength', 'regional_id', 'administration_id', 'prayer_house_id',
                'list_per_page', 'ui_theme', 'ui_palette', 'ui_sidebar', 'is_super_admin', 'email_verified_at',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function profileInitials(): string
    {
        return collect(explode(' ', (string) $this->name))
            ->filter()
            ->take(2)
            ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('') ?: '?';
    }

    public function hasProfilePhoto(): bool
    {
        return filled($this->profile_photo_path)
            && Storage::disk('public')->exists($this->profile_photo_path);
    }

    public function profilePhotoUrl(): ?string
    {
        if (! $this->hasProfilePhoto()) {
            return null;
        }

        $url = Storage::disk('public')->url($this->profile_photo_path);
        $version = $this->updated_at?->timestamp ?? time();

        return $url.(str_contains($url, '?') ? '&' : '?').'v='.$version;
    }

    public function resolvedListPerPage(): int
    {
        $min = (int) config('nexus.pagination.per_page_min', 5);
        $max = (int) config('nexus.pagination.per_page_max', 200);
        $default = (int) config('nexus.pagination.default', 20);
        $value = (int) ($this->list_per_page ?? $default);

        return max($min, min($max, $value > 0 ? $value : $default));
    }

    public function resolvedUiTheme(): string
    {
        $allowed = config('nexus.ui.themes', ['light', 'dark']);
        $theme = $this->ui_theme ?? config('nexus.ui.default_theme', 'light');

        return in_array($theme, $allowed, true) ? $theme : 'light';
    }

    public function resolvedUiPalette(): string
    {
        $allowed = config('nexus.ui.palettes', ['blue']);
        $palette = $this->ui_palette ?? config('nexus.ui.default_palette', 'blue');

        return in_array($palette, $allowed, true) ? $palette : 'blue';
    }

    public function resolvedUiSidebar(): string
    {
        $allowed = config('nexus.ui.sidebar_modes', ['expanded', 'icons']);
        $sidebar = $this->ui_sidebar ?? config('nexus.ui.default_sidebar', 'expanded');

        return in_array($sidebar, $allowed, true) ? $sidebar : 'expanded';
    }

    /**
     * @return list<int>
     */
    public function accessibleRegionalIds(): array
    {
        if ($this->isSuperAdmin()) {
            return Regional::query()->pluck('id')->all();
        }

        $ids = collect();
        if ($this->regional_id) {
            $ids->push((int) $this->regional_id);
        }
        $this->loadMissing(['administration', 'prayerHouse.administration']);
        if ($this->administration) {
            $ids->push((int) $this->administration->regional_id);
        }
        if ($this->prayerHouse?->administration) {
            $ids->push((int) $this->prayerHouse->administration->regional_id);
        }

        return $ids->unique()->values()->all();
    }

    /**
     * Escrita na secretaria (avisos, alterações, coletas, lista de batismos, quem atende).
     * Ver relatórios não basta.
     */
    public function canMaintainSecretaria(): bool
    {
        return $this->isSuperAdmin()
            || $this->can('editar_evento')
            || $this->can('gerenciar_avisos')
            || $this->can('moderar_conteudo');
    }

    public function canAccessRegional(Regional $regional): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->regional_id === $regional->id) {
            return true;
        }

        if ($this->administration_id && $this->administration?->regional_id === $regional->id) {
            return true;
        }

        if ($this->prayer_house_id && $this->prayerHouse?->administration?->regional_id === $regional->id) {
            return true;
        }

        return false;
    }

    public function canAccessAdministration(Administration $administration): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->administration_id === $administration->id) {
            return true;
        }

        if ($this->prayer_house_id && $this->prayerHouse?->administration_id === $administration->id) {
            return true;
        }

        if ($this->regional_id === $administration->regional_id) {
            return true;
        }

        return false;
    }

    public function canAccessPrayerHouse(PrayerHouse $prayerHouse): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->prayer_house_id === $prayerHouse->id) {
            return true;
        }

        if ($this->administration_id === $prayerHouse->administration_id) {
            return true;
        }

        return $this->regional_id === $prayerHouse->administration?->regional_id;
    }

    /**
     * Regional operacional derivada do vínculo direto (regional) ou indireto (administração / casa).
     */
    public function organizationalRegionalId(): ?int
    {
        $this->loadMissing(['administration', 'prayerHouse.administration']);
        if ($this->regional_id) {
            return (int) $this->regional_id;
        }
        if ($this->administration_id && $this->administration) {
            return (int) $this->administration->regional_id;
        }
        if ($this->prayer_house_id && $this->prayerHouse?->administration) {
            return (int) $this->prayerHouse->administration->regional_id;
        }

        return null;
    }

    /**
     * Utilizador com `gerenciar_usuarios` pode gerir outro utilizador na mesma árvore regional (super-admin gere todos).
     */
    public function canManageUser(User $target): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (! $this->can('gerenciar_usuarios')) {
            return false;
        }

        if ($target->is_super_admin) {
            return false;
        }

        $targetRegional = $target->organizationalRegionalId();
        if ($targetRegional === null) {
            return false;
        }

        return in_array($targetRegional, $this->accessibleRegionalIds(), true);
    }

    public function canAccessMeetingRoom(MeetingRoom $room): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $room->loadMissing(['owner', 'assignments']);

        $owner = $room->owner;
        if ($owner instanceof Administration && $this->canAccessAdministration($owner)) {
            return true;
        }

        if ($owner instanceof PrayerHouse && $this->canAccessPrayerHouse($owner)) {
            return true;
        }

        foreach ($room->assignments as $assignment) {
            $assignable = $assignment->assignable;
            if ($assignable instanceof Regional && $this->canAccessRegional($assignable)) {
                return true;
            }
            if ($assignable instanceof Administration && $this->canAccessAdministration($assignable)) {
                return true;
            }
            if ($assignable instanceof PrayerHouse && $this->canAccessPrayerHouse($assignable)) {
                return true;
            }
        }

        return false;
    }
}

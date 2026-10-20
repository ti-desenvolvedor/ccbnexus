<?php

namespace App\Models;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MinistryServant extends Model
{
    use LogsActivity;

    protected $fillable = [
        'regional_id',
        'full_name',
        'name_key',
        'ministry',
        'public_position_id',
        'phone',
        'comum_prayer_house_id',
        'ordained_on',
    ];

    protected $casts = [
        'ordained_on' => 'date',
    ];

    public function regional(): BelongsTo
    {
        return $this->belongsTo(Regional::class);
    }

    public function comum(): BelongsTo
    {
        return $this->belongsTo(PrayerHouse::class, 'comum_prayer_house_id');
    }

    public function attendanceHouses(): BelongsToMany
    {
        return $this->belongsToMany(PrayerHouse::class, 'ministry_servant_prayer_house')
            ->withTimestamps()
            ->orderBy('prayer_houses.name');
    }

    public function formattedPhone(): string
    {
        $digits = (string) ($this->phone ?? '');
        if (strlen($digits) !== 11) {
            return $digits;
        }

        return sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 5), substr($digits, 7, 4));
    }

    public static function nameKey(string $name): string
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '');
        $name = mb_strtolower($name);
        $from = ['á', 'à', 'â', 'ã', 'ä', 'é', 'è', 'ê', 'ë', 'í', 'ì', 'î', 'ï', 'ó', 'ò', 'ô', 'õ', 'ö', 'ú', 'ù', 'û', 'ü', 'ç', 'ñ'];
        $to = ['a', 'a', 'a', 'a', 'a', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'c', 'n'];

        return str_replace($from, $to, $name);
    }

    public static function phoneDigits(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }

    public static function register(int $regionalId, string $name, int $positionId, string $phone): self
    {
        return self::saveProfile($regionalId, $name, $positionId, $phone, null, []);
    }

    /**
     * @param  list<int|string>  $attendanceHouseIds
     */
    public static function saveProfile(
        int $regionalId,
        string $name,
        int $positionId,
        string $phone,
        ?int $comumId,
        array $attendanceHouseIds,
        ?int $exceptId = null,
        ?string $ordainedOn = null,
    ): self {
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '');
        $digits = self::phoneDigits($phone);
        $position = PublicPosition::query()
            ->active()
            ->forRegionalContext($regionalId)
            ->whereKey($positionId)
            ->whereHas('department', fn ($department) => $department->where('slug', 'ministerio'))
            ->first();

        if (mb_strlen($name) < 5 || ! str_contains($name, ' ')) {
            throw ValidationException::withMessages([
                'servant_name' => 'Informe o nome completo.',
            ]);
        }
        if (! $position) {
            throw ValidationException::withMessages([
                'servant_position_id' => 'Escolha um cargo do ministério já cadastrado nesta regional.',
            ]);
        }
        if ($digits !== '' && ! preg_match('/^[1-9][1-9]9\d{8}$/', $digits)) {
            throw ValidationException::withMessages([
                'servant_phone' => 'Informe o DDD e o celular com 11 dígitos. Ex.: 11 98765-4321.',
            ]);
        }

        $key = self::nameKey($name);
        $duplicateName = self::query()
            ->where('regional_id', $regionalId)
            ->where('name_key', $key)
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->exists();
        if ($duplicateName) {
            throw ValidationException::withMessages([
                'servant_name' => 'Já existe um cadastro com este nome nesta regional.',
            ]);
        }
        if ($digits !== '' && self::query()
            ->where('regional_id', $regionalId)
            ->where('phone', $digits)
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->exists()) {
            throw ValidationException::withMessages([
                'servant_phone' => 'Já existe um cadastro com este celular nesta regional.',
            ]);
        }

        $catalogIds = self::prayerHousesForRegional($regionalId)->pluck('id')->map(fn ($id) => (int) $id)->all();
        if ($comumId !== null && ! in_array($comumId, $catalogIds, true)) {
            throw ValidationException::withMessages([
                'comum_prayer_house_id' => 'Escolha uma casa de oração ativa desta regional.',
            ]);
        }

        $attendanceIds = array_values(array_unique(array_filter(
            array_map(static fn ($id) => (int) $id, $attendanceHouseIds),
            static fn (int $id) => $id > 0,
        )));
        if (array_diff($attendanceIds, $catalogIds) !== []) {
            throw ValidationException::withMessages([
                'attendance_prayer_house_ids' => 'Escolha apenas casas de oração ativas desta regional.',
            ]);
        }

        $ordainedOn = self::nullableDate($ordainedOn);

        $payload = [
            'regional_id' => $regionalId,
            'full_name' => $name,
            'name_key' => $key,
            'ministry' => $position->name,
            'public_position_id' => $position->id,
            'phone' => $digits,
            'comum_prayer_house_id' => $comumId,
            'ordained_on' => $ordainedOn,
        ];

        if ($exceptId) {
            $servant = self::query()->whereKey($exceptId)->where('regional_id', $regionalId)->first();
            if (! $servant) {
                throw ValidationException::withMessages([
                    'servant_name' => 'Cadastro não encontrado nesta regional.',
                ]);
            }
            $servant->update($payload);
        } else {
            $servant = self::query()->create($payload);
        }

        $servant->attendanceHouses()->sync($attendanceIds);

        return $servant;
    }

    /**
     * Data opcional no formato do campo HTML (Y-m-d). Vazio permanece nulo.
     */
    private static function nullableDate(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = DateTimeImmutable::getLastErrors();
        $hasError = is_array($errors) && (($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0);
        if ($date === false || $hasError || $date->format('Y-m-d') !== $value) {
            throw ValidationException::withMessages([
                'ordained_on' => 'Informe uma data válida ou deixe em branco.',
            ]);
        }

        return $value;
    }

    /**
     * Casas de oração ativas da regional. A comum e as igrejas de atendimento usam este catálogo.
     *
     * @return Collection<int, PrayerHouse>
     */
    public static function prayerHousesForRegional(int $regionalId): Collection
    {
        return PrayerHouse::query()
            ->where('is_active', true)
            ->whereHas('administration', fn ($query) => $query->where('regional_id', $regionalId))
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }

    public function assignTo(Event $event): void
    {
        $templateId = EventRoleTemplate::query()->where('slug', 'atende')->value('id');
        if (! $templateId) {
            throw ValidationException::withMessages([
                'servant_name' => 'O papel Atende não está cadastrado.',
            ]);
        }

        $existing = $event->roleAssignments()->where('event_role_template_id', $templateId)->first();
        $payload = [
            'assignee_label' => $this->full_name,
            'user_id' => null,
            'meta' => array_merge($existing?->meta ?? [], [
                'ministry_servant_id' => $this->id,
            ]),
        ];

        if ($existing) {
            $existing->update($payload);

            return;
        }

        $event->roleAssignments()->create($payload + [
            'event_role_template_id' => $templateId,
        ]);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['regional_id', 'full_name', 'ministry', 'public_position_id', 'phone', 'comum_prayer_house_id', 'ordained_on'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}

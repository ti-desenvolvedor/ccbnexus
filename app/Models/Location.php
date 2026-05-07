<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Location extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'name',
        'line1',
        'number',
        'complement',
        'district',
        'city',
        'state',
        'postal_code',
        'country',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function regionals(): HasMany
    {
        return $this->hasMany(Regional::class);
    }

    public function administrations(): HasMany
    {
        return $this->hasMany(Administration::class);
    }

    public function prayerHouses(): HasMany
    {
        return $this->hasMany(PrayerHouse::class);
    }

    public function meetingRooms(): HasMany
    {
        return $this->hasMany(MeetingRoom::class);
    }

    public function parkings(): HasMany
    {
        return $this->hasMany(Parking::class);
    }

    /**
     * Resumo em uma linha para listagens (logradouro, número, bairro, cidade/UF, CEP).
     */
    public function formattedAddressSummary(): string
    {
        $street = trim(implode(', ', array_filter([(string) $this->line1, (string) ($this->number ?? '')])));
        $parts = array_filter([
            $street !== '' ? $street : null,
            $this->complement ? (string) $this->complement : null,
            $this->district ? (string) $this->district : null,
            trim(implode(' / ', array_filter([(string) $this->city, (string) $this->state]))),
            $this->postal_code ? (string) $this->postal_code : null,
        ]);

        return $parts !== [] ? implode(' · ', $parts) : '—';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'line1', 'number', 'complement', 'district', 'city', 'state', 'postal_code', 'country'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}

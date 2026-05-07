<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventRsvp extends Model
{
    public const PARTICIPATION_NOT_ANSWERED = 'not_answered';

    public const PARTICIPATION_YES = 'yes';

    public const PARTICIPATION_NO = 'no';

    public const PARTICIPATION_MAYBE = 'maybe';

    public const PRESENCE_IN_PERSON = 'in_person';

    public const PRESENCE_ONLINE = 'online';

    protected $fillable = [
        'event_id',
        'user_id',
        'participation',
        'presence_mode',
        'meal_coffee',
        'meal_lunch',
        'meal_snack',
        'meal_dinner',
    ];

    protected $casts = [
        'meal_coffee' => 'boolean',
        'meal_lunch' => 'boolean',
        'meal_snack' => 'boolean',
        'meal_dinner' => 'boolean',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

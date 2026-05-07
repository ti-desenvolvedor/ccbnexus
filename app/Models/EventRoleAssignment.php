<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventRoleAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'event_role_template_id',
        'user_id',
        'assignee_label',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function roleTemplate(): BelongsTo
    {
        return $this->belongsTo(EventRoleTemplate::class, 'event_role_template_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

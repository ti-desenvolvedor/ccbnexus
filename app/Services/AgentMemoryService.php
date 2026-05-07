<?php

namespace App\Services;

use App\Models\AgentMemoryEntry;
use App\Models\User;

class AgentMemoryService
{
    /**
     * @param  array<string, mixed>|null  $tags
     * @param  array<string, mixed>|null  $context
     */
    public function append(
        string $phase,
        string $title,
        ?string $stage = null,
        ?string $body = null,
        ?array $tags = null,
        ?array $context = null,
        string $actor = 'system',
        ?User $user = null,
    ): AgentMemoryEntry {
        return AgentMemoryEntry::query()->create([
            'phase' => $phase,
            'stage' => $stage,
            'title' => $title,
            'body' => $body,
            'actor' => $actor,
            'user_id' => $user?->id,
            'tags' => $tags,
            'context' => $context,
        ]);
    }
}

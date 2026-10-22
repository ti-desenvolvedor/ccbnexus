<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Regional;
use App\Models\User;
use Database\Seeders\CcbReferenceDataSeeder;
use Database\Seeders\RegionalEmbuArtesAgenda2026Seeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgendaEmbuArtes2026SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_excel_keys_idempotently_and_respects_channel_flags(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(CcbReferenceDataSeeder::class);
        User::factory()->create(['is_super_admin' => true]);
        $this->seed(RegionalEmbuArtesAgenda2026Seeder::class);
        $this->seed(RegionalEmbuArtesAgenda2026Seeder::class);

        $regional = Regional::query()->where('slug', 'embu-das-artes')->firstOrFail();
        $parents = Event::query()
            ->where('regional_id', $regional->id)
            ->where('is_occurrence', false)
            ->whereNotNull('meta->excel_key')
            ->get();

        $this->assertGreaterThanOrEqual(20, $parents->count());
        $this->assertSame($parents->count(), $parents->pluck('meta.excel_key')->unique()->count());

        $eaCentral = $parents->firstWhere('meta.excel_key', 'batismo-ea-central');
        $this->assertNotNull($eaCentral);
        $this->assertTrue((bool) $eaCentral->include_on_baptism_list);
        $nextYearEnd = sprintf('%d-12-31', max(2026, (int) now()->year) + 1);
        $this->assertSame($nextYearEnd, optional($eaCentral->recurrence_until)->toDateString());
        $this->assertTrue(
            Event::query()
                ->where('parent_event_id', $eaCentral->id)
                ->where('is_occurrence', true)
                ->where('starts_at', '>=', ((int) substr($nextYearEnd, 0, 4)).'-01-01')
                ->exists()
        );

        $ago = $parents->firstWhere('meta.excel_key', 'ago-ea');
        $this->assertNotNull($ago);
        $this->assertFalse((bool) $ago->include_on_baptism_list);
        $this->assertTrue((bool) $ago->whatsapp_enabled);

        $children = Event::query()
            ->where('regional_id', $regional->id)
            ->where('is_occurrence', true)
            ->where('parent_event_id', $eaCentral->id)
            ->count();
        $this->assertGreaterThan(0, $children);

        $mocidadeKeys = $parents
            ->filter(fn (Event $e) => str_starts_with((string) ($e->meta['excel_key'] ?? ''), 'mocidade-'))
            ->pluck('meta.excel_key')
            ->sort()
            ->values()
            ->all();
        $this->assertContains('mocidade-its-jacira-nov', $mocidadeKeys);
        $this->assertContains('mocidade-ea-santa-tereza', $mocidadeKeys);
        $this->assertContains('mocidade-eg-central', $mocidadeKeys);
        $this->assertGreaterThanOrEqual(20, count($mocidadeKeys));

        $from = '2026-10-15 00:00:00';
        $to = '2026-12-31 23:59:59';
        $mocidadeInWindow = Event::query()
            ->where('regional_id', $regional->id)
            ->where('include_on_baptism_list', true)
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->whereBetween('starts_at', [$from, $to])
            ->whereHas('eventType', fn ($q) => $q->whereIn('slug', ['reuniao-mocidade', 'mocidade-online']))
            ->orderBy('starts_at')
            ->get(['id', 'title', 'starts_at', 'location_id']);

        // Lista R00 15/10: Nov Jacira+Saint Moritz; Dez EG/Santa Tereza/JUQ/Vila Santa Maria; + RMO 29/11.
        $this->assertGreaterThanOrEqual(7, $mocidadeInWindow->count());
        $dates = $mocidadeInWindow->map(fn (Event $e) => $e->starts_at->format('Y-m-d'))->unique()->sort()->values()->all();
        $this->assertContains('2026-11-08', $dates);
        $this->assertContains('2026-12-13', $dates);
        $this->assertContains('2026-11-29', $dates);

        $this->assertSame('2026-01-24', $eaCentral->starts_at->toDateString());
        $this->assertSame('monthly_nth', $eaCentral->recurrence_frequency);

        $rrm = $parents->firstWhere('meta.excel_key', 'rrm-ea');
        $this->assertNotNull($rrm);
        $this->assertSame('2026-01-15', $rrm->starts_at->toDateString());
        $this->assertSame('monthly_nth', $rrm->recurrence_frequency);

        $organistas = $parents->firstWhere('meta.excel_key', 'organistas-ea');
        $this->assertNotNull($organistas);
        $this->assertSame('2026-04-26', $organistas->starts_at->toDateString());

        $cipo = Event::query()
            ->where('regional_id', $regional->id)
            ->where('is_occurrence', false)
            ->where('status', 'published')
            ->where('title', 'Batismo EG - Cipó-Guaçu')
            ->orderBy('starts_at')
            ->get();
        $this->assertSame(
            ['2026-04-03', '2026-10-25'],
            $cipo->map(fn (Event $e) => $e->starts_at->toDateString())->all(),
        );
        $this->assertTrue($cipo->every(fn (Event $e) => $e->recurrence_frequency === 'monthly_nth'));

        $ra = $parents->firstWhere('meta.excel_key', 'ra-ea');
        $this->assertNotNull($ra);
        $this->assertSame('monthly_nth', $ra->recurrence_frequency);
        $this->assertSame('2026-01-20', $ra->starts_at->toDateString());

        $naked = $parents->filter(fn (Event $e) => $e->recurrence_frequency === null && $e->status === 'published');
        $this->assertCount(0, $naked);
    }
}

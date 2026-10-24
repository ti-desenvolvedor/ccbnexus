<?php

namespace App\Services;

use App\Models\BaptismListReport;
use Illuminate\Support\Facades\DB;

class BaptismListSectionVisibility
{
    /**
     * @param  list<string>  $sectionKeys
     */
    public function hide(BaptismListReport $report, array $sectionKeys): void
    {
        $this->apply($report, $sectionKeys, false);
    }

    /**
     * @param  list<string>  $sectionKeys
     */
    public function show(BaptismListReport $report, array $sectionKeys): void
    {
        $this->apply($report, $sectionKeys, true);
    }

    /**
     * Secções que o utilizador ocultou. Itens novos destas secções nascem ocultos ao regenerar.
     *
     * @return list<string>
     */
    public static function hiddenSections(?BaptismListReport $report): array
    {
        $hidden = $report?->meta['hidden_sections'] ?? [];

        return is_array($hidden) ? array_values(array_filter($hidden, 'is_string')) : [];
    }

    /**
     * @param  list<string>  $sectionKeys
     */
    private function apply(BaptismListReport $report, array $sectionKeys, bool $visible): void
    {
        $keys = array_values(array_intersect($sectionKeys, array_keys(BaptismListReportBuilder::SECTION_ORDER)));
        if ($keys === []) {
            return;
        }

        DB::transaction(function () use ($report, $keys, $visible) {
            $report->items()->whereIn('section_key', $keys)->update(['is_visible' => $visible]);

            $hidden = self::hiddenSections($report);
            $hidden = $visible
                ? array_diff($hidden, $keys)
                : array_unique(array_merge($hidden, $keys));

            $report->update([
                'meta' => array_merge($report->meta ?? [], ['hidden_sections' => array_values($hidden)]),
            ]);
        });
    }
}

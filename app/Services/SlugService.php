<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SlugService
{
    /**
     * Gera slug único no escopo indicado (colunas => valores).
     */
    public function generate(string $source, string $table, array $scope = [], ?int $ignoreId = null): string
    {
        $base = Str::slug($source);
        if ($base === '') {
            $base = 'item';
        }

        $candidate = $base;
        $suffix = 2;

        while ($this->exists($table, $scope, $candidate, $ignoreId)) {
            $candidate = $base.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }

    /**
     * @param  array<string, int|string|null>  $scope
     */
    private function exists(string $table, array $scope, string $slug, ?int $ignoreId): bool
    {
        $query = DB::table($table)->where('slug', $slug);

        foreach ($scope as $column => $value) {
            if ($value === null) {
                $query->whereNull($column);
            } else {
                $query->where($column, $value);
            }
        }

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }
}

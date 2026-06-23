<?php

declare(strict_types=1);

namespace App\Modules\Measurement\Services;

/**
 * Compares two flat maps of numeric measurement fields and decides whether the
 * change is "significant" (exceeds a per-field threshold) and therefore needs
 * supervisor approval. Keys may be namespaced (e.g. "shirt.chest"); the
 * threshold is looked up by full key, then bare field name, then 'default'.
 */
final class SignificantChangeDetector
{
    /**
     * @param  array<string, mixed>  $previous
     * @param  array<string, mixed>  $current
     * @param  array<string, float|int>  $thresholds
     * @return array{fields_changed: array<string, array{from: mixed, to: mixed}>, threshold_breached: array<string, float>, is_significant: bool}
     */
    public function detect(array $previous, array $current, array $thresholds): array
    {
        $fieldsChanged = [];
        $breached = [];
        $significant = false;

        $keys = array_unique(array_merge(array_keys($previous), array_keys($current)));

        foreach ($keys as $key) {
            $old = $previous[$key] ?? null;
            $new = $current[$key] ?? null;

            if (!is_numeric($old) && !is_numeric($new)) {
                continue;
            }

            $oldNum = (float) $old;
            $newNum = (float) $new;

            if ($oldNum === $newNum) {
                continue;
            }

            $fieldsChanged[$key] = ['from' => $old, 'to' => $new];

            $delta = abs($newNum - $oldNum);
            if ($delta > $this->thresholdFor($key, $thresholds)) {
                $breached[$key] = $delta;
                $significant = true;
            }
        }

        return [
            'fields_changed' => $fieldsChanged,
            'threshold_breached' => $breached,
            'is_significant' => $significant,
        ];
    }

    /**
     * @param  array<string, float|int>  $thresholds
     */
    private function thresholdFor(string $key, array $thresholds): float
    {
        $bare = str_contains($key, '.') ? (string) substr(strrchr($key, '.') ?: '.', 1) : $key;

        return (float) ($thresholds[$key] ?? $thresholds[$bare] ?? $thresholds['default'] ?? 1.0);
    }
}

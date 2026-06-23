<?php

declare(strict_types=1);

namespace App\Modules\Measurement\Http\Requests\Concerns;

trait ValidatesMeasurementData
{
    /**
     * Build numeric rules for every shirt/pant field plus free-text note rules,
     * derived from config/measurements.php.
     *
     * @return array<string, mixed>
     */
    protected function measurementRules(): array
    {
        $min = (int) config('measurements.field_min', 0);
        $max = (int) config('measurements.field_max', 100);
        $notes = (int) config('measurements.note_count', 5);

        $rules = [
            'shirt_data' => ['nullable', 'array'],
            'pant_data' => ['nullable', 'array'],
        ];

        foreach (['shirt' => 'shirt_fields', 'pant' => 'pant_fields'] as $section => $configKey) {
            foreach ((array) config("measurements.{$configKey}", []) as $field) {
                $rules["{$section}_data.{$field}"] = ['nullable', 'numeric', "min:{$min}", "max:{$max}"];
            }

            for ($i = 1; $i <= $notes; $i++) {
                $rules["{$section}_data.note_{$i}"] = ['nullable', 'string', 'max:500'];
            }
        }

        return $rules;
    }
}

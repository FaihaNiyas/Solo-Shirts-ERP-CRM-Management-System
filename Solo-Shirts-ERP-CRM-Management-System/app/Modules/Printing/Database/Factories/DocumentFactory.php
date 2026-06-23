<?php

declare(strict_types=1);

namespace App\Modules\Printing\Database\Factories;

use App\Modules\Identity\Models\Branch;
use App\Modules\Order\Models\Order;
use App\Modules\Printing\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Document>
 */
final class DocumentFactory extends Factory
{
    protected $model = Document::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $hash = hash('sha256', (string) Str::uuid());

        return [
            'branch_id' => Branch::factory(),
            'kind' => Document::KIND_JOB_CARD,
            'reference_type' => Order::class,
            'reference_id' => Order::factory(),
            'disk' => 'local',
            'path' => 'documents/job_card/' . $hash . '.pdf',
            'content_hash' => $hash,
            'size_bytes' => 1024,
            'generated_at' => now(),
        ];
    }
}

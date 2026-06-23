<?php

declare(strict_types=1);

namespace App\Modules\Shared\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $key
 * @property int $user_id
 * @property string $method
 * @property string $path
 * @property string $request_hash
 * @property int|null $response_status
 * @property string|null $response_body
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class IdempotencyKey extends Model
{
    protected $table = 'idempotency_keys';

    protected $fillable = [
        'key',
        'user_id',
        'method',
        'path',
        'request_hash',
        'response_status',
        'response_body',
    ];

    protected $casts = [
        'response_status' => 'integer',
    ];
}

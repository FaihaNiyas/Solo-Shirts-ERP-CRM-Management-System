<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Models;

use App\Modules\Reporting\Database\Factories\DailyBranchStatFactory;
use App\Modules\Shared\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $branch_id
 * @property Carbon $on_date
 * @property int $orders_received
 * @property int $orders_delivered
 * @property int $revenue_paise
 * @property int $defects
 */
final class DailyBranchStat extends Model
{
    /** @use HasFactory<DailyBranchStatFactory> */
    use BelongsToBranch, HasFactory;

    protected $fillable = [
        'branch_id',
        'on_date',
        'orders_received',
        'orders_delivered',
        'revenue_paise',
        'defects',
    ];

    protected $casts = [
        'on_date' => 'date',
        'orders_received' => 'integer',
        'orders_delivered' => 'integer',
        'revenue_paise' => 'integer',
        'defects' => 'integer',
    ];

    protected static function newFactory(): DailyBranchStatFactory
    {
        return DailyBranchStatFactory::new();
    }
}

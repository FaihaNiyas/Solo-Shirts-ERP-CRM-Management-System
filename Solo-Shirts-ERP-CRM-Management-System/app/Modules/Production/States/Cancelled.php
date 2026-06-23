<?php

declare(strict_types=1);

namespace App\Modules\Production\States;

final class Cancelled extends ProductionState
{
    public static string $name = 'cancelled';
}

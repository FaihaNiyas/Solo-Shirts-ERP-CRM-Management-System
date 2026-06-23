<?php

declare(strict_types=1);

namespace App\Modules\Production\States;

final class ReadyForDelivery extends ProductionState
{
    public static string $name = 'ready_for_delivery';
}

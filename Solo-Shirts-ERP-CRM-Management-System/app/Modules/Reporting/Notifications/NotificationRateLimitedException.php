<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Notifications;

use RuntimeException;

/**
 * Raised by a channel when the provider's per-minute quota is exhausted. The
 * dispatcher catches it and leaves the message queued for a later retry.
 */
final class NotificationRateLimitedException extends RuntimeException {}

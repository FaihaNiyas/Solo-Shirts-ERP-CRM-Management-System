<?php

declare(strict_types=1);

return [
    /*
    | Roles for which two-factor authentication is mandatory. A user holding any
    | of these roles must satisfy a TOTP challenge at login once 2FA is enabled.
    */
    'two_factor_required_roles' => env('APP_ENV') === 'local' ? [] : ['Owner', 'Admin', 'Accountant'],

    /*
    | Login throttle: after this many failed attempts for the same (email, ip)
    | within the decay window, further attempts are locked out.
    */
    'login_max_attempts' => (int) env('LOGIN_MAX_ATTEMPTS', 5),
    'login_decay_minutes' => (int) env('LOGIN_DECAY_MINUTES', 15),
];

<?php

return [
    'command' => 'source:monitor',
    'ttl' => (int) (getenv('REDIS_LOCK_TTL') ?: 660),
    'keys' => [
        'pending' => 'queue:pending',
        'active' => 'queue:active',
        'paused' => 'queue:paused',
    ],
    'schedules' => [
        'openrouter:agy' => '0 */6 * * *',
        'fl:agy' => '@15minutes',
        'hh:agy' => '@15minutes',
    ],
];

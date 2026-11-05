<?php

return [
    'ttl' => (int) (getenv('REDIS_LOCK_TTL') ?: 660),
    'keys' => [
        'pending' => 'queue:pending',
        'active' => 'queue:active',
        'paused' => 'queue:paused',
    ],
    'schedules' => [
        'source:monitor --openrouter=agy' => '0 */6 * * *',
        'source:monitor --fl=agy' => '@15minutes',
        'source:monitor --hh=agy' => '@15minutes',
        'source:report --daily' => '0 18 * * *',
    ],
];

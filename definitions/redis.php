<?php

return [
    'host' => getenv('REDIS_HOST') ?: '127.0.0.1',
    'port' => (int) (getenv('REDIS_PORT') ?: 6379),
    'timeout' => 2.0,
    'lock_ttl' => (int) (getenv('REDIS_LOCK_TTL') ?: 660),
];

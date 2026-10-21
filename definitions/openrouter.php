<?php

return [
    'base_uri' => 'https://openrouter.ai/api/v1/',
    'nat64_ips' => array_values(array_filter(explode(',', getenv('OPENROUTER_NAT64_IPS') ?: ''))),
    'timeout' => 10.0,
];

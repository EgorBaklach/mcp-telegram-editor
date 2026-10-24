<?php

use Magistrale\Clients\{Fl, OpenRouter, Telegram};

return [
    Fl::class => [
        'base_uri' => 'https://www.fl.ru/',
        'timeout' => 10.0,
        'cookies' => true,
        'headers' => [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
            'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
        ],
        'filter' => [
            'active' => true,
            'prof_groups' => [
                ['id' => 5],
                ['id' => 2],
                ['id' => 31],
                ['id' => 34],
                ['id' => 37],
                ['id' => 40],
                ['id' => 41],
                ['id' => 30],
            ],
            'professions' => [],
            'country_id' => 0,
            'city_id' => 0,
            'cost_min' => ['amount' => 0, 'currency' => 'RUB'],
            'cost_max' => ['amount' => 0, 'currency' => 'RUB'],
            'keywords' => ['parser', 'bitrix', 'битрикс', 'laravel', 'ларавел', 'парсер', 'chatgpt', 'php', 'react', 'реакт', 'пхп', 'filament', 'филамент', 'zcart'],
            'pro_only' => true,
            'safe_deal' => false,
            'my_specs' => false,
            'urgent' => false,
            'without_executor' => false,
            'less_offers' => false,
            'without_cost' => true,
            'konkurs_end_days_from' => null,
            'konkurs_end_days_to' => null,
            'without_pro' => true,
            'kind' => '',
            'prof_not_clear' => false,
        ],
    ],
    OpenRouter::class => [
        'base_uri' => 'https://openrouter.ai/api/v1/',
        'nat64_ips' => array_values(array_filter(explode(',', getenv('OPENROUTER_NAT64_IPS') ?: ''))),
        'timeout' => 10.0,
    ],
    Telegram::class => [
        'base_uri' => 'https://api.telegram.org/bot'.getenv('TELEGRAM_BOT_TOKEN').'/',
        'chat_id' => getenv('TELEGRAM_CHAT_ID'),
        'proxy' => getenv('TELEGRAM_PROXY') ?: null,
        'timeout' => 10.0,
    ],
];

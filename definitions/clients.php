<?php

use GuzzleHttp\Exception\GuzzleException;
use Magistrale\Clients\{FLClient, HHClient, OpenRouterClient, TelegramClient};

return [
    FLClient::class => [
        'base_uri' => 'https://www.fl.ru/',
        'timeout' => 10.0,
        'cookies' => true,
        'decode_content' => 'gzip, deflate',
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
        'criteria' => [
            'ttl' => '-30 days',
            'min_budget' => 3000,
            'min_length' => 150,
            'stop_words' => [
                'бпла', 'медкомисс', 'списание долгов', 'участок земли', 'судимос',
                'куплю исходный код', 'привлечения клиентов', 'всплывающие окна',
                'всплывающее окно', 'объединение дублирующих карточек', 'долго грузится', 'adguard',
                'репетитор', 'ученик', 'курсов', 'диплом', 'лидогенерац',
            ],
        ],
    ],
    HHClient::class => [
        'base_uri' => 'https://ekaterinburg.hh.ru/',
        'proxy' => getenv('HH_PROXY') ?: null,
        'timeout' => 10.0,
        'cookies' => true,
        'decode_content' => 'gzip, deflate',
        'headers' => [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
            'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
        ],
        'filter' => [
            'text' => 'php OR laravel OR bitrix OR битрикс',
            'professional_role' => [96, 104],
            'order_by' => 'publication_time',
            'enable_snippets' => 'true',
            'items_on_page' => 100,
        ],
        'criteria' => [
            'ttl' => '-30 days',
            'min_length' => 150,
            'stop_words' => [
                '1с:предприятие', '1с: предприятие', '1с предприятие', 'платформы 1с', 'конфигураций 1с',
                '1с:бухгалтерия', '1с:управление', '1с:комплексная', '1с:зуп', '1с:ут', '1с:ка', '1с:erp', '1с:унф',
                '1с 8', '1c 8', '1с8', 'аналитик 1с', '1с разработчик', '1с-разработчик', 'стажер', 'стажёр',
            ],
        ],
    ],
    OpenRouterClient::class => [
        'base_uri' => 'https://openrouter.ai/api/v1/',
        'nat64_ips' => array_values(array_filter(explode(',', getenv('OPENROUTER_NAT64_IPS') ?: ''))),
        'timeout' => 10.0,
        'decode_content' => 'gzip, deflate',
    ],
    TelegramClient::class => [
        'base_uri' => 'https://api.telegram.org/bot'.getenv('TELEGRAM_BOT_TOKEN').'/',
        'chat_id' => getenv('TELEGRAM_CHAT_ID'),
        'admin_id' => getenv('TELEGRAM_ADMIN_ID'),
        'proxy' => getenv('TELEGRAM_PROXY') ?: null,
        'timeout' => 10.0,
        'templates' => [
            GuzzleException::class => '<b>⚠️ %s [%s:%d]</b>'.PHP_EOL.'<pre>%s</pre>'.PHP_EOL.'<i>ℹ️ Сетевой сбой перехвачен, пайп продолжил работу.</i>',
            Throwable::class => '<b>❌ %s [%s:%d]</b>'.PHP_EOL.'<pre>%s</pre>',
        ],
    ],
];

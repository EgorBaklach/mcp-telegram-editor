<?php

return [
    'base_uri' => 'https://api.telegram.org/bot'.getenv('TELEGRAM_BOT_TOKEN').'/',
    'chat_id' => getenv('TELEGRAM_CHAT_ID'),
    'proxy' => getenv('TELEGRAM_PROXY') ?: null,
    'timeout' => 10.0,
];

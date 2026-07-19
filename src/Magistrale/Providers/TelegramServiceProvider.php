<?php namespace Magistrale\Providers;

use Framework\Providers\ProviderAbstract;
use GuzzleHttp\Client;
use RuntimeException;

class TelegramServiceProvider extends ProviderAbstract
{
    protected array $provides = [
        Client::class
    ];

    public function register(): void
    {
        $this->container()->addShared(Client::class, function(): Client
        {
            $token = getenv('TELEGRAM_BOT_TOKEN');

            if(!$token)
            {
                throw new RuntimeException('Telegram bot token is not configured in environment.');
            }

            return new Client([
                'base_uri' => "https://api.telegram.org/bot{$token}/",
                'timeout'  => 10.0,
            ]);
        });
    }
}

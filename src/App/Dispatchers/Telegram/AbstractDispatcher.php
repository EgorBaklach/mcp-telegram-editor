<?php namespace App\Dispatchers\Telegram;

use Illuminate\Database\Capsule\Manager as Capsule;
use Magistrale\Clients\TelegramClient as Client;
use Magistrale\Contracts\{DispatcherInterface, MacroableInterface};
use Magistrale\Traits\MacroableTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

abstract class AbstractDispatcher implements DispatcherInterface, MacroableInterface
{
    use MacroableTrait;

    protected ?ResponseInterface $response = null;

    protected const method = '';

    public function __construct(protected readonly Capsule $capsule, protected readonly LoggerInterface $logger, protected readonly Client $client) {}

    public function dispatch(mixed $payload = null): bool
    {
        try
        {
            if(!$this->response = $this->client->{static::method}($payload)) throw new RuntimeException('Telegram API request failed: '.static::method);

            $this->logger->info('Telegram API result: '.(string) $this->response->getBody()); return true;
        }
        catch(Throwable $e)
        {
            $this->logger->error('Failed to operate with Telegram: '.(string) $e);
        }

        return false;
    }
}

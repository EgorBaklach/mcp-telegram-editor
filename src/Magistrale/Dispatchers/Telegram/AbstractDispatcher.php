<?php namespace Magistrale\Dispatchers\Telegram;

use Illuminate\Database\Capsule\Manager as Capsule;
use Magistrale\Dispatchers\DispatcherInterface;
use Magistrale\HTTPClients\Telegram as Client;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

abstract class AbstractDispatcher implements DispatcherInterface
{
    protected const method = '';
    protected ?ResponseInterface $lastResponse = null;

    public function __construct(protected readonly Capsule $capsule, protected readonly LoggerInterface $logger, protected readonly Client $client) {}

    public function dispatch(mixed $payload = null): bool
    {
        if(!$payload) return false;

        try
        {
            $this->lastResponse = $response = $this->client->{static::method}($payload);

            if($response->getStatusCode() !== 200) throw new RuntimeException($response->getStatusCode());

            $this->logger->info("Telegram API result: " . (string) $response->getBody()); return true;
        }
        catch(Throwable $e)
        {
            $this->logger->error("Failed to operate with Telegram: " . (string) $e);
        }

        return false;
    }
}

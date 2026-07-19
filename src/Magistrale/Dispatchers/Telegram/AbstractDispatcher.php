<?php namespace Magistrale\Dispatchers\Telegram;

use Illuminate\Database\Capsule\Manager as Capsule;
use Magistrale\Dispatchers\DispatcherInterface;
use Magistrale\Clients\Telegram as Client;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

abstract class AbstractDispatcher implements DispatcherInterface
{
    protected ?ResponseInterface $response = null;

    protected const method = '';

    public function __construct(protected readonly Capsule $capsule, protected readonly LoggerInterface $logger, protected readonly Client $client) {}

    public function dispatch(mixed $payload = null): bool
    {
        if(!$payload) return false;

        try
        {
            $this->response = $this->client->{static::method}($payload);

            if($this->response->getStatusCode() !== 200) throw new RuntimeException($this->response->getStatusCode());

            $this->logger->info("Telegram API result: " . (string) $this->response->getBody()); return true;
        }
        catch(Throwable $e)
        {
            $this->logger->error("Failed to operate with Telegram: " . (string) $e);
        }

        return false;
    }
}

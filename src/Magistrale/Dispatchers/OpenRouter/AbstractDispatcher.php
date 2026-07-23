<?php namespace Magistrale\Dispatchers\OpenRouter;

use Illuminate\Database\Capsule\Manager as Capsule;
use Magistrale\Dispatchers\DispatcherInterface;
use Magistrale\Clients\OpenRouter as Client;
use Psr\Log\LoggerInterface;

abstract class AbstractDispatcher implements DispatcherInterface
{
    public function __construct(protected readonly Capsule $capsule, protected readonly LoggerInterface $logger, protected readonly Client $client) {}
}

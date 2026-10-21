<?php namespace Magistrale\Dispatchers\OpenRouter;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Traits\Macroable;
use Magistrale\Dispatchers\DispatcherInterface;
use Magistrale\Clients\OpenRouter as Client;
use Magistrale\Mixins\MacroableInterface;
use Psr\Log\LoggerInterface;

abstract class AbstractDispatcher implements DispatcherInterface, MacroableInterface
{
    use Macroable;

    public function __construct(protected readonly Capsule $capsule, protected readonly LoggerInterface $logger, protected readonly Client $client) {}
}

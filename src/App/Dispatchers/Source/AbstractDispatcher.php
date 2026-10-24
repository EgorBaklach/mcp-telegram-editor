<?php namespace App\Dispatchers\Source;

use App\Clients\AbstractClient as Client;
use Illuminate\Database\Capsule\Manager as Capsule;
use Magistrale\Contracts\{DispatcherInterface, MacroableInterface, OperableInterface, ResultInterface};
use Magistrale\Traits\{MacroableTrait, OperableTrait};
use Psr\Log\LoggerInterface;

abstract class AbstractDispatcher implements DispatcherInterface, ResultInterface, MacroableInterface, OperableInterface
{
    use MacroableTrait, OperableTrait;

    public readonly Client $client;

    protected array $results = [];

    public function __construct(protected readonly Capsule $capsule, protected readonly LoggerInterface $logger) {}

    public function setClient(Client $client): void
    {
        $this->client = $client;
    }

    public function getResults(): array
    {
        return $this->results;
    }
}

<?php namespace App\Dispatchers\Source;

use Illuminate\Database\Capsule\Manager as Capsule;
use Magistrale\Clients\AbstractClient as Client;
use Magistrale\Contracts\{DispatcherInterface, MacroableInterface, ObservableInterface, OperableInterface, ResultInterface};
use Magistrale\Traits\{MacroableTrait, ObservableTrait, OperableTrait};
use Psr\Log\LoggerInterface;

abstract class AbstractDispatcher implements DispatcherInterface, ResultInterface, MacroableInterface, OperableInterface, ObservableInterface
{
    use MacroableTrait, OperableTrait, ObservableTrait;

    public readonly Client $client;

    protected array $results = [];

    protected array $upsert = [];

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

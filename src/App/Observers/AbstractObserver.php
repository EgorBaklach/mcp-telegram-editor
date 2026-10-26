<?php namespace App\Observers;

use Magistrale\Clients\TelegramClient as Client;
use Magistrale\Contracts\{CallbackInterface, MacroableInterface, ObserverInterface, OperableInterface};
use Magistrale\Traits\{MacroableTrait, OperableTrait};
use Psr\Log\LoggerInterface;

abstract class AbstractObserver implements ObserverInterface, MacroableInterface, OperableInterface
{
    use MacroableTrait, OperableTrait;

    public function __construct(protected readonly Client $client, protected readonly LoggerInterface $logger) {}

    public function setCallback(CallbackInterface $callback): void
    {
        $callback->container['observer.notify'] = [
            fn(mixed $payload) => $this->prepare($payload),
            fn(string $payload) => $this->client->notify($payload),
        ];

        $this->callback = $callback;
    }
}

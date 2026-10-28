<?php namespace App\Tools\FL;

use App\Dispatchers\Source\FLDispatcher;

final class CheckNewOrdersTool
{
    public function __construct(private FLDispatcher $dispatcher) {}

    public function check(): string
    {
        if(!$this->dispatcher->dispatch()) return json_encode(['status' => 'error', 'message' => 'sync failed']);

        return json_encode(['status' => 'ok', 'new_orders' => $this->dispatcher->getResults()], JSON_UNESCAPED_UNICODE);
    }
}

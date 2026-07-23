<?php namespace App\Tools;

use Magistrale\Dispatchers\OpenRouter\SyncDispatcher;

final class CheckNewModelsTool
{
    public function __construct(private SyncDispatcher $syncDispatcher) {}

    public function checkNewModels(): string
    {
        if(!$this->syncDispatcher->dispatch()) return json_encode(['status' => 'error', 'message' => 'sync failed']);

        return json_encode(['status' => 'ok', 'new_models' => $this->syncDispatcher->getResults()], JSON_UNESCAPED_UNICODE);
    }
}

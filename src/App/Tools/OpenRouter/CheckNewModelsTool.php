<?php namespace App\Tools\OpenRouter;

use App\Dispatchers\Source\OpenRouterDispatcher;

final class CheckNewModelsTool
{
    public function __construct(private OpenRouterDispatcher $dispatcher) {}

    public function checkNewModels(): string
    {
        if(!$this->dispatcher->dispatch()) return json_encode(['status' => 'error', 'message' => 'sync failed']);

        return json_encode(['status' => 'ok', 'new_models' => $this->dispatcher->getResults()], JSON_UNESCAPED_UNICODE);
    }
}

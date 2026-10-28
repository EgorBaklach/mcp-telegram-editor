<?php namespace App\Tools\HH;

use App\Dispatchers\Source\HHDispatcher;

final class CheckNewVacanciesTool
{
    public function __construct(private HHDispatcher $dispatcher) {}

    public function check(): string
    {
        if(!$this->dispatcher->dispatch()) return json_encode(['status' => 'error', 'message' => 'sync failed']);

        return json_encode(['status' => 'ok', 'new_vacancies' => $this->dispatcher->getResults()], JSON_UNESCAPED_UNICODE);
    }
}

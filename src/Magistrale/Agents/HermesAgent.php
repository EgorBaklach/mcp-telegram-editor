<?php namespace Magistrale\Agents;

class HermesAgent extends AbstractAgent
{
    public function prepare(string $sourceName): void {}

    public function run(string $prompt): bool
    {
        $this->initializeAcp();
        $this->createSession();
        $this->sendPrompt($prompt);

        return true;
    }

    protected function initializeAcp(): void {}

    protected function createSession(): void {}

    protected function sendPrompt(string $prompt): void {}

    protected function approvePermission(string|int $requestId): void {}

    public function cleanup(): void {}
}

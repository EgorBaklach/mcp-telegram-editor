<?php namespace Magistrale\Agents;

class HermesAgent extends AbstractAgent
{
    public function prepare(string $source): void {}

    public function run(string $prompt): bool
    {
        $this->initialize();
        $this->session();
        $this->prompt($prompt);

        return true;
    }

    protected function initialize(): void {}

    protected function session(): void {}

    protected function prompt(string $prompt): void {}

    protected function approve(string|int $id): void {}

    public function cleanup(): void {}
}

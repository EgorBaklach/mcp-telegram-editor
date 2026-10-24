<?php namespace Magistrale\Contracts;

interface AgentInterface
{
    public function prepare(string $sourceName): void;
    public function run(string $prompt): bool;
    public function getOutput(): string;
    public function getErrorOutput(): string;
    public function cleanup(): void;
}

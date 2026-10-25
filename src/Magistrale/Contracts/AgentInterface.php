<?php namespace Magistrale\Contracts;

interface AgentInterface
{
    public function prepare(string $source): void;
    public function run(string $prompt): bool;
    public function output(): string;
    public function error(): string;
    public function cleanup(): void;
}

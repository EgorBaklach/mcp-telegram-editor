<?php namespace Framework\Contracts\Console;

use Symfony\Component\Console\Command\Command;

interface ConsoleInterface
{
    public function add(Command $command): ?Command;
    public function find(string $name): Command;
}

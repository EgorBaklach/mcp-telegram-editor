<?php namespace Framework\Contracts\Console;

use Psr\Container\ContainerInterface;

interface CommandInterface
{
    public function setContainer(ContainerInterface $container): void;
    public function construct(): void;
}
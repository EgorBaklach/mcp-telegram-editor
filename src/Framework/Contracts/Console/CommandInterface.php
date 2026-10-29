<?php namespace Framework\Contracts\Console;

use League\Container\DefinitionContainerInterface;

interface CommandInterface
{
    public function setContainer(DefinitionContainerInterface $container): self;
    public function construct(): void;
}
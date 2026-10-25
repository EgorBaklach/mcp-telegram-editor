<?php namespace Magistrale\Agents;

use League\Container\DefinitionContainerInterface;
use Magistrale\Contracts\AgentInterface;
use Symfony\Component\Filesystem\Filesystem;

abstract class AbstractAgent implements AgentInterface
{
    protected array $options;

    protected string $output = '';

    protected string $error = '';

    public function __construct(DefinitionContainerInterface $container, protected readonly Filesystem $fs)
    {
        $this->options = $container->get('agents');
    }

    public function output(): string
    {
        return $this->output;
    }

    public function error(): string
    {
        return $this->error;
    }
}

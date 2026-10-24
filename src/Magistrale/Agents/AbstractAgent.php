<?php namespace Magistrale\Agents;

use League\Container\DefinitionContainerInterface;
use Magistrale\Contracts\AgentInterface;
use Symfony\Component\Filesystem\Filesystem;

abstract class AbstractAgent implements AgentInterface
{
    protected array $options;

    protected string $output = '';

    protected string $errorOutput = '';

    public function __construct(DefinitionContainerInterface $container, protected readonly Filesystem $fs)
    {
        $this->options = $container->get('agents');
    }

    public function getOutput(): string
    {
        return $this->output;
    }

    public function getErrorOutput(): string
    {
        return $this->errorOutput;
    }
}

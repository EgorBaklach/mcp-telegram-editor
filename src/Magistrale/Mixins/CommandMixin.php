<?php namespace Magistrale\Mixins;

use Cli\Commands\AbstractCommand;
use Closure;

class CommandMixin
{
    public function hasOption(): Closure
    {
        return function(string $name): bool
        {
            /** @var AbstractCommand $this */ return $this->input->getOption($name) !== false;
        };
    }

    public function getOption(): Closure
    {
        return function(string $name): mixed
        {
            /** @var AbstractCommand $this */ $value = $this->input->getOption($name); return $value === true ? null : $value;
        };
    }
}

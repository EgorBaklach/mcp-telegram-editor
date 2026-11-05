<?php namespace Magistrale\Dispatchers\Migration;

use Illuminate\Database\Capsule\Manager as Capsule;
use League\Plates\Engine as Template;
use Magistrale\Contracts\{ConsoleLoggerInterface, DispatcherInterface, OperableInterface};
use Magistrale\Traits\OperableTrait;
use Symfony\Component\Filesystem\Filesystem;

abstract class AbstractDispatcher implements DispatcherInterface, OperableInterface
{
    use OperableTrait;

    public function __construct(
        public readonly Capsule $capsule,
        protected readonly Filesystem $fs,
        protected readonly Template $template,
        public readonly ConsoleLoggerInterface $logger
    ){}
}

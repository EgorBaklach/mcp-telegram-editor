<?php namespace Tests;

use Framework\Application;
use League\Container\DefinitionContainerInterface;
use PHPUnit\Framework\TestCase as BaseTestCase;
use ReflectionClass;

abstract class TestCase extends BaseTestCase
{
    protected DefinitionContainerInterface $container;

    protected function bootContainer(): DefinitionContainerInterface
    {
        $app = Application::make(require __DIR__.'/../bootstrap/machine.php');

        $property = (new ReflectionClass($app))->getProperty('container');

        return $this->container = $property->getValue($app);
    }
}

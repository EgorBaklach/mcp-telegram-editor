<?php namespace Framework\Contracts\Provider;

use League\Container\ServiceProvider\ServiceProviderInterface;
use Psr\Container\ContainerInterface;

interface ProviderInterface extends ServiceProviderInterface
{
    public function container(): ?ContainerInterface;
}

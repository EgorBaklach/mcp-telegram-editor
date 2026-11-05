<?php namespace Magistrale\Providers;

use Framework\Providers\AbstractProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use League\Plates\Engine as Template;

final class TemplateProvider extends AbstractProvider implements BootableServiceProviderInterface
{
    private array $config = [];

    protected array $provides = [
        Template::class,
    ];

    public function boot(): void
    {
        $this->config = $this->container()->get('template');
    }

    public function register(): void
    {
        $this->container()->add(Template::class, fn(): Template => new class(...$this->config) extends Template
        {
            public function render($name, array $data = []): string
            {
                $template = $this->make($name); return $template->render(array_map(fn(mixed $v): mixed => is_string($v) ? $template->e($v) : $v, $data));
            }
        });
    }
}

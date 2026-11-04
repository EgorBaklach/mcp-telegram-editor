<?php namespace Magistrale\Helpers;

use Magistrale\Contracts\OperatorInterface;
use Symfony\Component\Filesystem\Filesystem;

class TemplateHelper implements OperatorInterface
{
    public function __construct(private readonly Filesystem $fs) {}

    public function __invoke(): array
    {
        return [
            'template.render' => fn(string $name, array $replace = []): string => strtr($this->fs->readFile('storage/app/templates/'.$name.'.php'), $replace),
        ];
    }
}

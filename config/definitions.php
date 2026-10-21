<?php

use Laminas\Stdlib\Glob;
use League\Container\Definition\{Definition, DefinitionAggregate};

return new DefinitionAggregate(array_map(fn(string $file): Definition => new Definition(basename($file, '.php'), require $file), Glob::glob('definitions/*.php', Glob::GLOB_BRACE, true)));

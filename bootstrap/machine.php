<?php

use Framework\Providers\ConfigProvider;
use Laminas\ConfigAggregator\ConfigAggregator;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv)->usePutenv(true)->bootEnv('.env');

$aggregator = new ConfigAggregator([ConfigProvider::class]);

return $aggregator->getMergedConfig();

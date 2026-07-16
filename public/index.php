<?php

use Framework\Application;

chdir(dirname(__DIR__));
require 'vendor/autoload.php';

Application::make(require 'bootstrap/machine.php')->run();
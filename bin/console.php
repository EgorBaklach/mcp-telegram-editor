<?php

error_reporting(E_ERROR | E_CORE_ERROR | E_COMPILE_ERROR);

use Framework\Application;

chdir(dirname(__DIR__));
require 'vendor/autoload.php';

Application::make(require 'bootstrap/machine.php')->cli();
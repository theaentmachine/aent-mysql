#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

use \TheAentMachine\Aent\ServiceAent;
use \TheAentMachine\AentMysql\Event\AddEvent;

$application = new ServiceAent("MySQL", new AddEvent());
$application->run();

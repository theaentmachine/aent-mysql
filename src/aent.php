#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

use TheAentMachine\AentApplication;
use TheAentMachine\AentMysql\Command\AddEventCommand;
use TheAentMachine\AentMysql\Command\RemoveEventCommand;

$application = new AentApplication();

$application->add(new AddEventCommand());
$application->add(new RemoveEventCommand());

$application->run();

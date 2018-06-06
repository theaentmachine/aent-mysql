#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Console\Application;
use TheAentMachine\AentMysql\Command\AddCommand;
use TheAentMachine\AentMysql\Command\DefaultCommand;
use TheAentMachine\AentMysql\Command\RemoveCommand;

$application = new Application();

try {
    $defaultCommand = new DefaultCommand();
    $application->add($defaultCommand);
    $application->setDefaultCommand($defaultCommand->getName());

    $application->add(new AddCommand());
    $application->add(new RemoveCommand());

    $application->run();
} catch (Exception $e) {
    exit(0);
}

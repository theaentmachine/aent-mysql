<?php

namespace TheAentMachine\AentMysql\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class RemoveCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName(Cst::REMOVE_EVENT)
            ->setDescription("An event from aenthill")
            ->setHelp('TODO')
            ->addArgument('payload', InputArgument::OPTIONAL, "The payload of the event");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $cmd = array("hermes", "dispatch", Cst::DELETE_DOCKER_SERVICE_EVENT, '"{}"');

        $process = Utils::startAndGetProcess($cmd, $output);
        $process->wait();
        if (!$process->isSuccessful()) {
            exit($process->getExitCode());
        }
    }
}

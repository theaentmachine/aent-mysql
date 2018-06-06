<?php

namespace TheAentMachine\AentMysql\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DefaultCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('run')
            ->setDescription('TODO')
            ->setHelp('TODO')
            ->addArgument('event', InputArgument::OPTIONAL, 'The name of the event')
            ->addArgument('payload', InputArgument::OPTIONAL, 'The payload of the event');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->write("   * in aent-mysql, ");
        $herculeCmd = array("hercule", "set:handled-events", Cst::ADD_EVENT, Cst::REMOVE_EVENT);
        $process = Utils::startAndGetProcess($herculeCmd, $output);
        $process->wait();
        if (!$process->isSuccessful()) {
            exit($process->getExitCode());
        }

        $event = $input->getArgument('event');
        if (empty($event)) {
            $output->writeln("no event");
            exit(0);
        }

        try {
            $command = $this->getApplication()->find($event);
        } catch (CommandNotFoundException $e) {
            $output->writeln("unrecognized event : " . $event);
            exit(0);
        }

        $args = array(
            'payload' => $input->getArgument('payload'),
        );
        $arrayInput = new ArrayInput($args);
        $output->writeln("event=" . $event);

        try {
            $command->run($arrayInput, $output);
        } catch (\Exception $e) {
            $output->writeln("error while running event : " . $event);
            exit(0);
        }
    }
}

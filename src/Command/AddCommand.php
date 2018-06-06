<?php

namespace TheAentMachine\AentMysql\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class AddCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName(Cst::ADD_EVENT)
            ->setDescription("An event from aenthill")
            ->setHelp('TODO')
            ->addArgument('payload', InputArgument::OPTIONAL, "The payload of the event");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // TODO: change it by checking if there is an aent which can handle NEW-DOCKER-SERVICE event
        if (!Utils::imageExistsInAenthill(Cst::DEFAULT_DOCKER_COMPOSE_IMG)
            && !Utils::imageExistsInAenthill(Cst::DEFAULT_KUBERNETES_IMG)) {
            $output->writeln(
                sprintf(
                    "   ⨯ Neither %s or %s image found in aenthill, please add them before",
                    Cst::DEFAULT_DOCKER_COMPOSE_IMG,
                    Cst::DEFAULT_KUBERNETES_IMG
                )
            );
            exit(1);
        }

        $helper = $this->getHelper('question');

        $serviceName = Utils::getAnswer($output, "Service name : ");

        $question = new ChoiceQuestion(
            'Select your mysql version : (default to latest)',
            array('latest', '8.0.11', '8.0', '8', '5.7.22', '5.7', '5.6.40', '5.6', '5.5.60', '5.5', '5'),
            0
        );
        $question->setErrorMessage('Version %s is invalid.');
        $version = $helper->ask($input, $output, $question);

        $image = "mysql/mysql-server:" . $version;

        $p = Utils::arrayFilterRec(Utils::getKeyValueArrayAnswer($output, "Ports : "));
        $ports = array();
        foreach ($p as $source => $target) {
            $ports[] = array("source" => $source, "target" => $target);
        }


        $envKeys = array('MYSQL_ROOT_PASSWORD', 'MYSQL_DATABASE');
        $env = Utils::arrayFilterRec(Utils::getValueFromKeyArrayAnswer($output, "Environment variable", $envKeys, '='));
        $environments = array();
        foreach ($env as $key => $value) {
            $environments[] = array("key" => $key, "value" => $value);
        }

        $payload = Utils::arrayFilterRec(array(
            Cst::SERVICE_NAME_KEY => $serviceName,
            "service" => array(
                "image" => $image,
                "ports" => $ports,
                "environments" => $environments
            ),
        ));

        $payloadStr = json_encode($payload, JSON_PRETTY_PRINT);

        $output->writeln($payloadStr);

        $payloadToDispatch = addslashes(json_encode($payload));
        $payloadToDispatch = '"' . $payloadToDispatch . '"';

        $cmd = array("hermes", "dispatch", Cst::NEW_DOCKER_SERVICE_INFO_EVENT, $payloadToDispatch);

        $process = Utils::startAndGetProcess($cmd, $output);
        $process->wait();
        if (!$process->isSuccessful()) {
            exit($process->getExitCode());
        }
    }
}

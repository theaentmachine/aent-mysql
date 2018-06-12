<?php

namespace TheAentMachine\AentMysql\Command;

use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use TheAentMachine\AentMysql\Aenthill\AenthillUtils;
use TheAentMachine\AentMysql\Aenthill\Enum\EventEnum;
use TheAentMachine\AentMysql\Aenthill\Exception\RequiredAentMissingException;
use TheAentMachine\AentMysql\Service\Enum\VolumeTypeEnum;
use TheAentMachine\AentMysql\Service\Service;
use TheAentMachine\EventCommand;
use TheAentMachine\Hercule;
use TheAentMachine\Hermes;

class AddEventCommand extends EventCommand
{
    protected function getEventName(): string
    {
        return EventEnum::ADD;
    }

    protected function executeEvent(?string $payload): void
    {
        Hercule::setHandledEvents(EventEnum::getHandledEvents());

        $receiverAents = AenthillUtils::findAentsByHandledEvent(EventEnum::NEW_DOCKER_SERVICE_INFO);
        if (count($receiverAents) === 0) {
            throw new RequiredAentMissingException(EventEnum::NEW_DOCKER_SERVICE_INFO);
        }

        $service = new Service();

        $helper = $this->getHelper('question');

        // serviceName
        $serviceName = CommandUtils::getAnswer($this->output, 'Service name : ');
        $service->setServiceName($serviceName);

        // image
        $question = new ChoiceQuestion(
            'Select your mysql version : (default to latest)',
            array('latest', '8.0.11', '8.0', '8', '5.7.22', '5.7', '5.6.40', '5.6', '5.5.60', '5.5', '5'),
            0
        );
        $question->setErrorMessage('Version %s is invalid.');
        $version = $helper->ask($this->input, $this->output, $question);
        $image = 'mysql/mysql-server:' . $version;
        $service->setImage($image);

        // environment
        $envKeys = array('MYSQL_ROOT_PASSWORD', 'MYSQL_DATABASE');
        $env = CommandUtils::getValueFromKeyArrayAnswer($this->output, 'Environment variables', $envKeys, '=');
        $environment = array();
        foreach ($env as $key => $value) {
            $environment[] = array('key' => $key, 'value' => $value);
        }
        $service->setEnvironment($environment);

        // volumes
        $addVolumeQuestion = new ConfirmationQuestion(
            "Do you want to add a volume ? [y/N]\n > ",
            false
        );

        $volumes = array();

        $doAddVolume = $helper->ask($this->input, $this->output, $addVolumeQuestion);
        if ($doAddVolume) {
            $addAnotherVolumeQuestion = new ConfirmationQuestion(
                "Do you want to add another volume ? [y/N]\n > ",
                false
            );
            $volumeTypeQuestion = new ChoiceQuestion(
                'Select your volume\'s type : (default to 0)',
                VolumeTypeEnum::getVolumeTypes(),
                00
            );
            $volumeTypeQuestion->setErrorMessage('Type %s is invalid.');
            $readOnlyQuestion = new ConfirmationQuestion(
                "Is your volume read only ? [y/N]\n > ",
                false
            );
            while ($doAddVolume) {
                $type = $helper->ask($this->input, $this->output, $volumeTypeQuestion);
                $source = CommandUtils::getAnswer($this->output, 'Your volume\'s source : ');
                $target = CommandUtils::getAnswer($this->output, 'Your volume\'s target : ');
                $readOnly = $helper->ask($this->input, $this->output, $readOnlyQuestion);
                $volumes[] = array(
                    'type' => $type,
                    'source' => $source,
                    'target' => $target,
                    'readOnly' => $readOnly,
                );
                $doAddVolume = $helper->ask($this->input, $this->output, $addAnotherVolumeQuestion);
            }
        }

        $service->setVolumes($volumes);
        $servicePayload = $service->serialize(true);

        $this->log->debug(json_encode($servicePayload, JSON_PRETTY_PRINT));

        Hermes::dispatchJson(EventEnum::NEW_DOCKER_SERVICE_INFO, $servicePayload);
    }
}

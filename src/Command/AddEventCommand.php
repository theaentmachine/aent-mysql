<?php

namespace TheAentMachine\AentMysql\Command;

use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use TheAentMachine\CommonEvents;
use TheAentMachine\EventCommand;
use TheAentMachine\Registry\RegistryClient;
use TheAentMachine\Service\Service;

class AddEventCommand extends EventCommand
{
    protected function getEventName(): string
    {
        return 'ADD';
    }

    protected function executeEvent(?string $payload): void
    {
        $helper = $this->getHelper('question');

        $commentEvents = new CommonEvents();
        $commentEvents->canDispatchServiceOrFail($helper, $this->input, $this->output);

        $service = new Service();


        // serviceName
        $question = new Question('Please enter the name of the MySQL service [mysql]: ', 'mysql');
        $question->setValidator(function (string $value) {
            $value = trim($value);
            if (!\preg_match('/^[a-zA-Z0-9_.-]+$/', $value)) {
                throw new \InvalidArgumentException('Invalid service name "'.$value.'". Service names can contain alphanumeric characters, and "_", ".", "-".');
            }

            return trim($value);
        });

        $serviceName = $helper->ask($this->input, $this->output, $question);
        $service->setServiceName($serviceName);

        // image
        $registryClient = new RegistryClient();
        $question = new ChoiceQuestion(
            'Select your mysql version : (default to latest)',
            $registryClient->getImageTagsOnDockerHub('mysql'),
            0
        );
        $question->setErrorMessage('Version %s is invalid.');
        $version = $helper->ask($this->input, $this->output, $question);
        $image = 'mysql:' . $version;
        $service->setImage($image);

        // environment
        $question = new Question('Please enter the root password (will be stored in MYSQL_ROOT_PASSWORD environment variable) : ', '');
        $question->setValidator(function (string $value) {
            $value = trim($value);
            if (empty($value)) {
                throw new \InvalidArgumentException('You must specify a root password.');
            }

            return trim($value);
        });

        $rootPassword = $helper->ask($this->input, $this->output, $question);
        $service->addSharedSecret('MYSQL_ROOT_PASSWORD', $rootPassword);
        $this->output->writeln('');
        $this->output->writeln('');

        $this->output->writeln('Do you want to initialize an empty database on container first start?');
        $question = new Question('If yes, please enter the database name (will be stored in MYSQL_DATABASE environment variable) : ', '');

        $dbName = trim($helper->ask($this->input, $this->output, $question));
        if (!empty($dbName)) {
            $service->addSharedEnvVariable('MYSQL_DATABASE', $dbName);
        }

        $this->output->writeln('');
        $this->output->writeln('');

        $userName = null;
        $password = null;
        $question = new ConfirmationQuestion('Do you want to create an additional (non root) user? [y] ');
        if ($helper->ask($this->input, $this->output, $question)) {
            $question = new Question('Please enter the name of the user : ', '');
            $question->setValidator(function (string $value) {
                $value = trim($value);
                if (empty($value)) {
                    throw new \InvalidArgumentException('You must specify a user name.');
                }

                return trim($value);
            });

            $userName = $helper->ask($this->input, $this->output, $question);
            $service->addSharedEnvVariable('MYSQL_USER', $userName);
            $this->output->writeln('');
            $this->output->writeln('');

            $question = new Question('Please enter the password : ', '');
            $question->setValidator(function (string $value) {
                $value = trim($value);
                if (empty($value)) {
                    throw new \InvalidArgumentException('You must specify a password.');
                }

                return trim($value);
            });

            $password = $helper->ask($this->input, $this->output, $question);
            $service->addSharedSecret('MYSQL_PASSWORD', $password);
            $this->output->writeln('');
            $this->output->writeln('');
        }

        $this->output->writeln('MySQL data will be stored in a dedicated volume.');
        $question = new Question('Please specify the volume name [mysql-data] : ', 'mysql-data');

        $volumeName = trim($helper->ask($this->input, $this->output, $question));

        $service->addNamedVolume($volumeName, '/var/lib/mysql');

        $commentEvents->dispatchService($service, $helper, $this->input, $this->output);

        $this->output->writeln('<question>Hey!</question> You just installed MySQL. I can install <info>PHPMyAdmin</info> for you for the administration.');

        $question = new ConfirmationQuestion('Do you want me to install PHPMyAdmin? [y] ', true);

        if ($helper->ask($this->input, $this->output, $question)) {
            $this->installPhpMyAdmin($serviceName, $rootPassword, $userName, $password);
        }
    }

    private function installPhpMyAdmin(string $mySqlServiceName, string $mySqlRootPassword, ?string $userName, ?string $password): void
    {
        $helper = $this->getHelper('question');

        $commentEvents = new CommonEvents();

        $service = new Service();


        // serviceName
        $question = new Question('Please enter the name of the PHPMyAdmin service [phpmyadmin]: ', 'phpmyadmin');
        $question->setValidator(function (string $value) {
            $value = trim($value);
            if (!\preg_match('/^[a-zA-Z0-9_.-]+$/', $value)) {
                throw new \InvalidArgumentException('Invalid service name "'.$value.'". Service names can contain alphanumeric characters, and "_", ".", "-".');
            }

            return trim($value);
        });

        $serviceName = $helper->ask($this->input, $this->output, $question);
        $service->setServiceName($serviceName);

        // image
        $registryClient = new RegistryClient();
        $question = new ChoiceQuestion(
            'Select your PHPMyAdmin version : (default to latest)',
            $registryClient->getImageTagsOnDockerHub('phpmyadmin/phpmyadmin'),
            0
        );
        $question->setErrorMessage('Version %s is invalid.');
        $version = $helper->ask($this->input, $this->output, $question);
        $image = 'phpmyadmin/phpmyadmin:' . $version;
        $service->setImage($image);

        $service->addContainerEnvVariable('PMA_HOST', $mySqlServiceName);
        $service->addSharedSecret('MYSQL_ROOT_PASSWORD', $mySqlRootPassword);

        if ($userName !== null) {
            $service->addSharedEnvVariable('MYSQL_USER', $userName);
        }
        if ($password !== null) {
            $service->addSharedSecret('MYSQL_PASSWORD', $password);
        }

        $commentEvents->dispatchService($service, $helper, $this->input, $this->output);
        $commentEvents->dispatchNewVirtualHost($helper, $this->input, $this->output, $serviceName);
    }
}

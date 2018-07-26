<?php

namespace TheAentMachine\AentMysql\Command;

use TheAentMachine\Aenthill\CommonEvents;
use TheAentMachine\Aenthill\CommonMetadata;
use TheAentMachine\Command\AbstractEventCommand;
use TheAentMachine\Service\Service;

class StartEventCommand extends AbstractEventCommand
{
    protected function getEventName(): string
    {
        return CommonEvents::START_EVENT;
    }

    /**
     * @param null|string $payload
     * @return null|string
     * @throws \TheAentMachine\Exception\CommonAentsException
     */
    protected function executeEvent(?string $payload): ?string
    {
        $aentHelper = $this->getAentHelper();
        $aentHelper->title('Adding a new MySQL service');
        $service = new Service();

        $environments = $aentHelper->getCommonQuestions()->askForEnvironments();
        $envTypes = [];
        if (null !== $environments) {
            $envTypes = array_map(function ($env) {
                return $env[CommonMetadata::ENV_TYPE_KEY];
            }, $environments);
            foreach ($envTypes as $envType) {
                $service->addDestEnvType($envType);
            }
        }

        // serviceName
        $serviceName = $aentHelper->getCommonQuestions()->askForServiceName('mysql', 'MySQL');
        $service->setServiceName($serviceName);

        // image
        $version = $aentHelper->getCommonQuestions()->askForDockerImageTag('mysql', 'MySQL');
        $image = 'mysql:' . $version;
        $service->setImage($image);

        // environment var
        $rootPassword = $this->getAentHelper()
            ->question('Root password')
            ->compulsory()
            ->setHelpText('The MySQL root password will be stored in the MYSQL_ROOT_PASSWORD environment variable.')
            ->ask();

        $service->addSharedSecret('MYSQL_ROOT_PASSWORD', $rootPassword);
        $this->output->writeln('');
        $this->output->writeln('');

        $isDbInit = $this->getAentHelper()
            ->question('Initialize database?')
            ->yesNoQuestion()
            ->setDefault('y')
            ->setHelpText('The database will be created on container first start. This parameter will populate the MYSQL_DATABASE environment variable.')
            ->ask();

        if ($isDbInit) {
            $dbName = $this->getAentHelper()
                ->question('Database name')
                ->compulsory()
                ->setHelpText('The database name will be stored in the MYSQL_DATABASE environment variable.')
                ->ask();
            $service->addSharedEnvVariable('MYSQL_DATABASE', trim($dbName));
        }

        $userName = null;
        $password = null;
        $isAdditionalUser = $this->getAentHelper()
            ->question('Create an additional (non root) user?')
            ->yesNoQuestion()
            ->setDefault('y')
            ->ask();

        if ($isAdditionalUser) {
            $userName = trim($this->getAentHelper()
                ->question('User name')
                ->compulsory()
                ->setHelpText('The database user name will be stored in the MYSQL_USER environment variable.')
                ->ask());

            $service->addSharedEnvVariable('MYSQL_USER', $userName);

            $password = trim($this->getAentHelper()
                ->question('User password')
                ->compulsory()
                ->setHelpText('The password will be stored in the MYSQL_PASSWORD environment variable.')
                ->ask());

            $service->addSharedSecret('MYSQL_PASSWORD', $password);
        }

        $this->output->writeln('MySQL data will be stored in a dedicated volume.');
        $volumeName = trim($this->getAentHelper()
            ->question('MySQL data volume name')
            ->compulsory()
            ->setDefault('mysql-data')
            ->setHelpText('The database files (located in the container in the /var/lib/mysql folder) will be stored in an external Docker volume.')
            ->ask());
        $service->addNamedVolume($volumeName, '/var/lib/mysql');

        CommonEvents::dispatchService($service);

        $this->getAentHelper()->spacer();

        if (count($envTypes) === 1 && $envTypes[0] === CommonMetadata::ENV_TYPE_PROD) {
            return null;
        }

        $this->output->writeln('<question>Hey!</question> You just installed MySQL. I can install <info>PHPMyAdmin</info> for you for the administration.');

        $isPhpMyAdmin = $isAdditionalUser = $this->getAentHelper()
            ->question('Do you want me to install PHPMyAdmin?')
            ->yesNoQuestion()
            ->setDefault('y')
            ->ask();

        if ($isPhpMyAdmin) {
            $this->installPhpMyAdmin($serviceName, $rootPassword, $userName, $password, $envTypes);
        }
        return null;
    }

    /**
     * @param string[] $envTypes
     */
    private function installPhpMyAdmin(string $mySqlServiceName, string $mySqlRootPassword, ?string $userName, ?string $password, array $envTypes = []): void
    {
        $aentHelper = $this->getAentHelper();

        $service = new Service();
        foreach ($envTypes as $envType) {
            $service->addDestEnvType($envType);
        }

        // serviceName
        $serviceName = $aentHelper->getCommonQuestions()->askForServiceName('phpmyadmin', 'PHPMyAdmin');
        $service->setServiceName($serviceName);

        // image
        $version = $aentHelper->getCommonQuestions()->askForDockerImageTag('phpmyadmin/phpmyadmin', 'PHPMyAdmin');
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

        $service->setNeedVirtualHost(true);
        CommonEvents::dispatchService($service);
    }
}

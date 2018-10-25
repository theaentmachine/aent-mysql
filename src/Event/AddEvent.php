<?php

namespace TheAentMachine\AentMysql\Event;

use Safe\Exceptions\ArrayException;
use Safe\Exceptions\FilesystemException;
use Safe\Exceptions\StringsException;
use TheAentMachine\Aent\Event\Service\AbstractServiceAddEvent;
use TheAentMachine\Aent\Event\Service\Model\Environments;
use TheAentMachine\Aent\Event\Service\Model\ServiceState;
use TheAentMachine\Prompt\Helper\ValidatorHelper;
use TheAentMachine\Service\Service;

final class AddEvent extends AbstractServiceAddEvent
{
    private const MYSQL_IMAGE = 'mysql';
    private const PHPMYADMIN_IMAGE = 'phpmyadmin/phpmyadmin';

    /**
     * @param Environments $environments
     * @return ServiceState[]
     * @throws ArrayException
     * @throws FilesystemException
     * @throws StringsException
     */
    protected function createServices(Environments $environments): array
    {
        $mysqlService = $this->createMySQLService();
        if ($environments->hasDevelopmentEnvironments() || $environments->hasTestEnvironments()) {
            $text = "\nDo you want a <info>phpMyAdmin</info> service?";
            $helpText = "<info>phpMyAdmin</info> will help you manage your database from your web browser.";
            $addPhpMyAdmin = $this->prompt->confirm($text, $helpText, null, true);
            if ($addPhpMyAdmin) {
                $this->output->writeln("\n👌 Alright, let's configure <info>phpMyAdmin</info>!");
                $phpMyAdminService = $this->createPhpMyAdminService($mysqlService);
                $mysqlServiceState = new ServiceState($mysqlService, $mysqlService, $mysqlService);
                $phpMyAdminServiceState = new ServiceState($phpMyAdminService, $phpMyAdminService, $phpMyAdminService);
                return [ $mysqlServiceState, $phpMyAdminServiceState ];
            }
            $this->output->writeln("\n👌 Alright, I'm not going to configure <info>phpMyAdmin</info>!");
            $serviceState = new ServiceState($mysqlService, $mysqlService, $mysqlService);
            return [ $serviceState ];
        }
        $this->output->writeln("\nAs you don't have <info>development</info> and <info>test</info> environments, I'm skipping <info>phpMyAdmin</info>!");
        $serviceState = new ServiceState($mysqlService, $mysqlService, $mysqlService);
        return [ $serviceState ];
    }

    /**
     * @return Service
     * @throws ArrayException
     * @throws FilesystemException
     * @throws StringsException
     */
    private function createMySQLService(): Service
    {
        $service = new Service();
        $service->setNeedBuild(false);
        $service->setServiceName($this->prompt->getPromptHelper()->getServiceName());
        $version = $this->prompt->getPromptHelper()->getVersion(self::MYSQL_IMAGE);
        $image = self::MYSQL_IMAGE . ':' . $version;
        $service->setImage($image);
        $service->addInternalPort(3306);
        // Argument necessary on Kubernetes clusters (the volume has a lost+found dir at the root)
        $service->addCommand('--ignore-db-dir=lost+found');
        $service->addCommand('--max_allowed_packet=512M');
        $rootPassword = $this->getMySQLPassword('Root password', 'The MySQL root password will be stored in the <info>MYSQL_ROOT_PASSWORD</info> environment variable.');
        $service->addSharedSecret('MYSQL_ROOT_PASSWORD', $rootPassword, 'The password for the root user of MySQL', $service->getServiceName());
        $databaseName = $this->getDatabaseName();
        $service->addSharedEnvVariable('MYSQL_DATABASE', trim($databaseName), 'A default database, created on MySQL first startup', $service->getServiceName());
        $databaseUser = $this->getMySQLUser();
        $service->addSharedEnvVariable('MYSQL_USER', $databaseUser, 'A default user, created on MySQL first startup', $service->getServiceName());
        $databaseUserPassword = $this->getMySQLPassword('Database user password', 'The MySQL user password will be stored in the <info>MYSQL_PASSWORD</info> environment variable.');
        $service->addSharedSecret('MYSQL_PASSWORD', $databaseUserPassword, 'The password of the default user, created on MySQL first startup', $service->getServiceName());
        $this->output->writeln("\n👌 Alright, we're almost done! Let's configure a named volume where your MySQL data will be stored!");
        $volumeName = $this->getMySQLNamedVolumeName();
        $service->addNamedVolume($volumeName, '/var/lib/mysql', false, 'This volume contains the whole MySQL data.');
        return $service;
    }

    /**
     * @return string
     */
    public function getDatabaseName(): string
    {
        $text = "\nDatabase name";
        $helpText = 'The database name will be stored in the <info>MYSQL_DATABASE</info> environment variable.';
        return $this->prompt->input($text, $helpText, null, true, ValidatorHelper::getAlphaWithAdditionalCharactersValidator(['-', '_'])) ?? '';
    }

    /**
     * @param string $text
     * @param string $helpText
     * @return string
     */
    private function getMySQLPassword(string $text, string $helpText): string
    {
        return $this->prompt->input($text, $helpText, null, true, ValidatorHelper::getAlphaWithAdditionalCharactersValidator(['-', '_'])) ?? '';
    }

    /**
     * @return string
     */
    private function getMySQLUser(): string
    {
        $text = "\nDatabase user";
        $helpText = 'The database user name will be stored in the <info>MYSQL_USER</info> environment variable.';
        return $this->prompt->input($text, $helpText, null, true, ValidatorHelper::getAlphaWithAdditionalCharactersValidator(['-', '_'])) ?? '';
    }

    /**
     * @return string
     */
    private function getMySQLNamedVolumeName(): string
    {
        $text = "\nMySQL named volume name";
        $helpText = 'The database files (located in the container in the <info>/var/lib/mysql</info> folder) will be stored in an external Docker volume.';
        return $this->prompt->input($text, $helpText, 'mysql-data', true, ValidatorHelper::getAlphaWithAdditionalCharactersValidator(['-', '_'])) ?? '';
    }

    /**
     * @param Service $mysqlService
     * @return Service
     * @throws ArrayException
     * @throws FilesystemException
     * @throws StringsException
     */
    private function createPhpMyAdminService(Service $mysqlService): Service
    {
        $service = new Service();
        $service->setNeedBuild(false);
        $service->setServiceName($this->prompt->getPromptHelper()->getServiceName());
        $version = $this->prompt->getPromptHelper()->getVersion(self::PHPMYADMIN_IMAGE);
        $image = self::PHPMYADMIN_IMAGE . ':' . $version;
        $service->setImage($image);
        $service->addInternalPort(80);
        $service->addContainerEnvVariable('PMA_HOST', $mysqlService->getServiceName());
        $service->addVirtualHost(80);
        return $service;
    }
}

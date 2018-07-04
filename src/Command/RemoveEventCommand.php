<?php

namespace TheAentMachine\AentMysql\Command;

use TheAentMachine\EventCommand;

class RemoveEventCommand extends EventCommand
{
    protected function getEventName(): string
    {
        return 'REMOVE';
    }

    protected function executeEvent(?string $payload): ?string
    {
        // TODO
        return null;
    }
}

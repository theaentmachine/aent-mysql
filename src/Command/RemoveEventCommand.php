<?php

namespace TheAentMachine\AentMysql\Command;

use TheAentMachine\AentMysql\Aenthill\Enum\EventEnum;
use TheAentMachine\EventCommand;

class RemoveEventCommand extends EventCommand
{
    protected function getEventName(): string
    {
        return EventEnum::REMOVE;
    }

    protected function executeEvent(?string $payload): void
    {
        // TODO
    }
}

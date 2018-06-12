<?php

namespace TheAentMachine\AentMysql\Command\Exception;

class PayloadException extends EventCommandException
{
    /**
     * @param string $event
     * @return PayloadException
     */
    public static function emptyPayload(string $event): PayloadException
    {
        return new self('payload missing for the event : ' . $event);
    }
}

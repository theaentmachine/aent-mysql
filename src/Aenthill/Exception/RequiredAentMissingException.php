<?php

namespace TheAentMachine\AentMysql\Aenthill\Exception;

use TheAentMachine\Exception\AenthillException;

class RequiredAentMissingException extends AenthillException
{
    /**
     * RequiredAentMissingException constructor.
     * @param string $requiredHandledEvent
     */
    public function __construct(string $requiredHandledEvent)
    {
        parent::__construct('there is no aent which can handle ' . $requiredHandledEvent . ' event, please add one first');
    }
}

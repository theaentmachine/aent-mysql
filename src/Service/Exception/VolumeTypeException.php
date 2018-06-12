<?php

namespace TheAentMachine\AentMysql\Service\Exception;

use TheAentMachine\AentMysql\Service\Enum\VolumeTypeEnum;

class VolumeTypeException extends ServiceException
{
    /** @var string */
    private $invalidVolumeType;

    /**
     * VolumeTypeException constructor.
     * @param string $invalidVolumeType
     */
    public function __construct(string $invalidVolumeType)
    {
        $this->invalidVolumeType = $invalidVolumeType;
        parent::__construct($this->invalidVolumeType . " is not a valid volume type. Are accepted : " . json_encode(VolumeTypeEnum::getVolumeTypes()));
    }
}

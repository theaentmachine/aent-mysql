<?php

namespace TheAentMachine\AentMysql\Aenthill;

use TheAentMachine\AentMysql\Aenthill\Enum\PheromoneEnum;
use TheAentMachine\AentMysql\Aenthill\Exception\ContainerProjectDirEnvVariableEmptyException;

class AenthillUtils
{
    /**
     * @param string $handledEvent
     * @return mixed[]
     * @throws ContainerProjectDirEnvVariableEmptyException
     */
    public static function findAentsByHandledEvent(string $handledEvent): array
    {
        $containerProjectDir = getenv(PheromoneEnum::PHEROMONE_CONTAINER_PROJECT_DIR);
        if (empty($containerProjectDir)) {
            throw new ContainerProjectDirEnvVariableEmptyException();
        }

        $aenthillJSONstr = file_get_contents($containerProjectDir . '/aenthill.json');
        $aenthillJSON = json_decode($aenthillJSONstr, true);

        $aents = array();

        if (isset($aenthillJSON['aents'])) {
            foreach ($aenthillJSON['aents'] as $aent) {
                if (array_key_exists('handled_events', $aent) && \in_array($handledEvent, $aent['handled_events'], true)) {
                    $aents[] = $aent;
                }
            }
        }
        return $aents;
    }
}

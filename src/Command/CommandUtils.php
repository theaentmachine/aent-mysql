<?php

namespace TheAentMachine\AentMysql\Command;

use Symfony\Component\Console\Output\OutputInterface;

class CommandUtils
{

    /**
     * @param OutputInterface $output
     * @param string $message
     * @return string
     */
    public static function getAnswer(OutputInterface $output, string $message): string
    {
        $output->write($message);
        return trim(fgets(STDIN));
    }

    /**
     * @param OutputInterface $output
     * @param string $message
     * @param bool $isInt
     * @return string[]
     */
    public static function getArrayAnswer(OutputInterface $output, string $message, bool $isInt = false): array
    {
        $array = array();
        $output->writeln($message);
        $output->write(' > ');
        $str = trim(fgets(STDIN));
        while (!empty($str)) {
            $array[] = $isInt ? (int)$str : $str;
            $output->write(' > ');
            $str = trim(fgets(STDIN));
        }
        return $array;
    }

    /**
     * @param OutputInterface $output
     * @param string $message
     * @return string[]
     */
    public static function getKeyValueArrayAnswer(OutputInterface $output, string $message): array
    {
        $array = array();
        $output->writeln($message . ' (in key:value or key=value format)');
        $output->write(' > ');
        $str = trim(fgets(STDIN));
        while (!empty($str)) {
            if (preg_match('/\A\w+[:=]{1}\w+\Z/', $str) !== 1) {
                $output->writeln('Incorrect format,  please retry :');
            } else {
                $delim = strpos($str, ':') === false ? '=' : ':';
                $split = explode($delim, $str);
                $key = $split[0];
                $value = $split[1];
                $array[$key] = $value;
            }
            $output->write(' > ');
            $str = trim(fgets(STDIN));
        }
        return $array;
    }

    /**
     * @param OutputInterface $output
     * @param string $message
     * @param mixed[] $keys
     * @param string $delim
     * @return mixed[]
     */
    public static function getValueFromKeyArrayAnswer(OutputInterface $output, string $message, array $keys, string $delim = ':'): array
    {
        $array = array();
        $output->writeln($message);
        foreach ($keys as $i => $key) {
            $output->write(' > ' . $key . $delim);
            $value = trim(fgets(STDIN));
            $array[$key] = $value;
        }
        return $array;
    }
}

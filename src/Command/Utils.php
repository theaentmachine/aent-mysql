<?php

namespace TheAentMachine\AentMysql\Command;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class Utils
{
    /**
     * Delete all key/value pairs with empty value by recursively using array_filter
     * @param array $input
     * @return mixed[] array
     */
    public static function arrayFilterRec(array $input): array
    {
        foreach ($input as &$value) {
            if (is_array($value)) {
                $value = Utils::arrayFilterRec($value);
            }
        }
        return array_filter($input);
    }

    /**
     * @param OutputInterface $output
     * @param string $message
     * @return string
     */
    public static function getAnswer(OutputInterface $output, string $message): string
    {
        $output->write($message);
        $str = trim(fgets(STDIN));
        return $str;
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
        $output->write(" > ");
        $str = trim(fgets(STDIN));
        while ($str != "") {
            array_push($array, $isInt ? intval($str) : $str);
            $output->write(" > ");
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
        $output->writeln($message . " (in key:value or key=value format)");
        $output->write(" > ");
        $str = trim(fgets(STDIN));
        while ($str != "") {
            if (preg_match('/\A\w+[:=]{1}\w+\Z/', $str) !== 1) {
                $output->writeln("Incorrect format,  please retry :");
            } else {
                $delim = strpos($str, ':') === false ? '=' : ':';
                $split = explode($delim, $str);
                $key = $split[0];
                $value = $split[1];
                $array[$key] = $value;
            }
            $output->write(" > ");
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
            $output->write(" > " . $key . $delim);
            $value = trim(fgets(STDIN));
            $array[$key] = $value;
        }
        return $array;
    }


    /**
     * Run a process and return the exit code
     * @param mixed $cmd command line in a single string or an array of strings
     * @param OutputInterface $output
     * @return Process
     */
    public static function startAndGetProcess($cmd, OutputInterface $output): Process
    {
        if (!is_array($cmd)) {
            $cmd = explode(' ', $cmd);
        }

        $process = new Process($cmd);

        $process->start();
        foreach ($process as $type => $buffer) {
            $output->write($buffer);
        }

        return $process;
    }

    /**
     * @param string $searchedImage
     * @return bool
     */
    public static function imageExistsInAenthill(string $searchedImage): bool
    {
        $aenthillJSON = json_decode(file_get_contents(Cst::AENTHILL_JSON_PATH), true);
        $images = $aenthillJSON['aents'];

        foreach ($images as $i) {
            $image = $i['image'];
            if (strpos($image, $searchedImage) !== false) {
                return true;
            }
        }
        return false;
    }
}

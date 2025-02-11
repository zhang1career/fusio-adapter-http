<?php

namespace Fusio\Adapter\Http\Component;

use Fusio\Engine\Exception\NotFoundException;

class FileDb
{
    /**
     * @var string data file path
     */
    private string $dataPath = "";

    /**
     * @var array
     */
    private array $dict = [];

    /**
     * @throws NotFoundException
     */
    public function __construct(string $dataPath)
    {
        // check arguments
        if (empty($dataPath) || !file_exists($dataPath) || !is_readable($dataPath)) {
            throw new NotFoundException('No readable data-file found');
        }
        $this->dataPath = $dataPath;

        // read data file
        $dataFile = fopen($this->dataPath, "r");
        if (!$dataFile) {
            return;
        }

        // build dictionary
        while (($line = fgets($dataFile)) !== false) {
            $line = trim($line);
            // skip empty lines
            if ($line === "") {
                continue;
            }
            // split line into key and values
            $parts = explode("\t", $line, 2);
            // skip invalid lines
            if (count($parts) < 2) {
                continue;
            }
            // add to dictionary
            $key = $parts[0];
            // split values by comma
            $values = explode(",", $parts[1]);

            $this->dict[$key] = $values;
        }

        fclose($dataFile);
    }

    /**
     * Randomly select a value from the dictionary
     * @param string $key
     * @return mixed|string
     */
    public function queryRandom(string $key): mixed
    {
        if (!$this->dict || !isset($this->dict[$key])) {
            return "";
        }
        $values = $this->dict[$key];
        return $values[array_rand($values)];
    }
}
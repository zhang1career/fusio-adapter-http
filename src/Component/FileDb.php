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
        if (empty($dataPath)) {
            throw new NotFoundException('No data-file path provided');
        }

        $this->dataPath = $dataPath;

        // if file doesn't exist, try to create it (and parent directory)
        if (!file_exists($this->dataPath)) {
            $this->createDataFile($this->dataPath);
        }

        // ensure file is readable
        if (!is_readable($this->dataPath)) {
            throw new NotFoundException('No readable data-file found');
        }

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
            $value = $parts[1];
            $this->dict[$key] = $value;
        }

        fclose($dataFile);
    }

    /**
     * Create the data file and parent directories if needed.
     * Extracted to a protected method to allow overriding in tests.
     *
     * @param string $path
     * @throws NotFoundException
     */
    protected function createDataFile(string $path): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0777, true) && !is_dir($dir)) {
                throw new NotFoundException('Unable to create directory for data-file: ' . $dir);
            }
        }

        $handle = @fopen($path, 'w');
        if ($handle === false) {
            throw new NotFoundException('Unable to create data-file: ' . $path);
        }
        fclose($handle);
    }

    /**
     * @param string $key
     * @return string
     */
    public function query(string $key): mixed
    {
        if (!$this->dict || !isset($this->dict[$key])) {
            return "";
        }
        return $this->dict[$key];
    }
}
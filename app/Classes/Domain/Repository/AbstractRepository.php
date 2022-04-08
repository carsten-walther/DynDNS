<?php

namespace CarstenWalther\DynDNS\Domain\Repository;

use InvalidArgumentException;

/**
 * AbstractRepository
 */
abstract class AbstractRepository
{
    /**
     * @var string
     */
    protected $file;

    /**
     * @var bool
     */
    protected $readOnly;

    /**
     * @var string
     */
    protected $delimiter;

    /**
     * @var resource
     */
    protected $content;

    /**
     * @param string $basePath
     * @param string $filename
     * @param bool $readOnly
     * @param string $delimiter
     */
    public function __construct(string $basePath, string $filename, bool $readOnly = false, string $delimiter = ';')
    {
        $this->delimiter = $delimiter;
        $this->readOnly = $readOnly;

        $this->checkPathAndFilename($basePath, $filename);
        $this->initFile($basePath, $filename);
        $this->init();
    }

    /**
     * @param string $basePath
     * @param string $filename
     * @return void
     */
    public function checkPathAndFilename(string $basePath, string $filename): void
    {
        if (!is_dir($basePath) && !mkdir($basePath, 0755) && !is_dir($basePath)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $basePath));
        }

        if (!file_exists($basePath . DIRECTORY_SEPARATOR . $filename)) {
            $file = fopen($basePath . DIRECTORY_SEPARATOR . $filename, 'wb') or die('Error opening file: ' . $basePath . DIRECTORY_SEPARATOR . $filename);
            fclose($file);
        }
    }

    /**
     * @param string $basePath
     * @param string $filename
     * @return $this
     */
    public function initFile(string $basePath, string $filename): AbstractRepository
    {
        if (is_file($basePath . DIRECTORY_SEPARATOR . $filename)) {
            if (is_readable($basePath . DIRECTORY_SEPARATOR . $filename)) {
                $this->file = $basePath . DIRECTORY_SEPARATOR . $filename;
            } else {
                throw new InvalidArgumentException('File must be readable.');
            }
        } else {
            throw new InvalidArgumentException('Path to file must be valid.');
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function init(): AbstractRepository
    {
        if (!$this->content) {
            $mode = $this->readOnly ? 'r' : 'r+';
            $this->content = fopen($this->file, $mode);
        }

        return $this;
    }
}

<?php

namespace Salesfire\Salesfire\Helper\Logger;

use SplFileObject;
use LimitIterator;

/**
 * Salesfire Logger
 *
 * Using Magento's native logger class as a replacement for Zend which is not included with the Magento framework anymore.
 * https://community.magento.com/t5/Magento-DevBlog/Migration-of-Zend-Framework-to-the-Laminas-Project/ba-p/443251
 *
 * @category   Salesfire
 * @package    Salesfire_Salesfire
 * @version    1.5.15
 */
class Logger
{
    protected $logger;

    const MAX_SIZE =  1024 * 1024 * 50; // 50 megabytes

    public function __construct($name = 'salesfire', array $handlers = [], array $processors = [])
    {
        $this->logger = new \Monolog\Logger($name, $handlers, $processors);
    }

    protected function getPath()
    {
        /** @var \App\Logger\Handler $handlers */
        $handlers = $this->logger->getHandlers();

        if (! is_array($handlers) || count($handlers) === 0) {
            return null;
        }

        return $handlers[0]->getUrl();
    }

    /**
     * Truncates the log file per iteration.
     */
    public function truncate($size = null)
    {
        $path = $this->getPath();

        if (! $path) {
            return false;
        }

        try {
            $file = new SplFileObject($path, 'c');

            $max_size = $size ?: self::MAX_SIZE;

            if ($file->getSize() >= $max_size) {
                $file->ftruncate(0);
                $file->rewind();
                $file = null;

                return true;
            }
        } catch (\Exception $e) {
            // Silence
        }

        return false;
    }

    public function getLastLines($number)
    {
        $path = $this->getPath();

        if (! $path) {
            return [];
        }

        try {
            $file = new SplFileObject($path, 'r');

            $file->seek(PHP_INT_MAX);

            $line_to = $file->key();

            $line_from = max(0, $line_to - $number);

            $lines = new LimitIterator($file, $line_from, $line_to);

            return iterator_to_array($lines);
        } catch (\Exception $e) {
            return [];
        }
    }

    public function info($message, array $context = [])
    {
        $this->logger->info($message, $context);
    }

    public function error($message, array $context = [])
    {
        $this->logger->error($message, $context);
    }
}

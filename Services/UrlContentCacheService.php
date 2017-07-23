<?php

namespace Dope\UtilBundle\Services;

use Dope\UtilBundle\Cache\CalculationCacheInterface;
use Dope\UtilBundle\Cache\CalculationCacheTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * @category  stocks
 * @copyright Copyright (c) 2017 Dominik Peuscher
 */
class UrlContentCacheService implements LoggerAwareInterface, CalculationCacheInterface
{
    use LoggerAwareTrait;
    use CalculationCacheTrait;

    const CURRENT_VERSION = 1;

    /**
     * @var string
     */
    protected $cacheFolder;

    /**
     * @var int
     */
    protected $timeframeCounter = 0;

    /**
     * @var int
     */
    protected $currentTimeframe = 0;

    /**
     * @var int
     */
    protected $timeframeSize = 60;

    /**
     * @var int
     */
    protected $timeframeLimit = 100;

    /**
     * @var float
     */
    protected $lastCall = 0.0;

    /**
     * @var float
     */
    protected $minTimeBetweenCalls = 0.5;

    /**
     * GolemParserService constructor.
     *
     * @param string $cacheFolder
     * @param LoggerInterface $logger
     */
    public function __construct($cacheFolder, LoggerInterface $logger)
    {
        $this->cacheFolder = $cacheFolder;
        $this->logger = $logger;
        $this->useCalculationCache = true;
    }

    /**
     * @param string $url
     * @return string
     */
    public function loadContent(string $url): string
    {
        return $this->getCached(md5($url), function () use ($url) {
            if (!is_dir($this->cacheFolder)) {
                $this->logger->notice('Cache-dir ' . $this->cacheFolder . ' does not exist. Creating...');
                if (mkdir($this->cacheFolder, 0777, true)) {
                    $this->logger->notice('... done');
                } else {
                    $this->logger->notice('... failed!');
                    $this->logger->warning('Creation of ' . $this->cacheFolder . ' failed!');
                }
            }
            $fileName = $this->cacheFolder . '/' . md5($url) . '_' . static::CURRENT_VERSION . '.cache';
            if (file_exists($fileName)) {
                $this->logger->debug('Cache hit filesystem cache for url: "' . $url . '" (' . $fileName . ')');
                return trim(file_get_contents($fileName));
            }
            $this->logger->debug('Cache miss filesystem cache for url: "' . $url . '" (' . $fileName . ')');

            $result = $this->downloadBalanced($url);

            $this->logger->debug('Cache missed. Write filesystem cache-file for url: "' . $url . '" (' . $fileName . ')');
            if (!file_put_contents($fileName, $result)) {
                $this->logger->warning('Could not write cache-file: "' . $url . '" (' . $fileName . ')');
            }

            return $result;
        });
    }

    /**
     * @param string $url
     * @return string
     */
    protected function downloadBalanced(string $url): string
    {
        $timeframe = floor(time() / $this->timeframeSize) * $this->timeframeSize;
        if ($timeframe != $this->currentTimeframe) {
            $this->currentTimeframe = $timeframe;
            $this->timeframeCounter = 0;
        }
        $this->timeframeCounter++;
        if ($this->timeframeCounter >= $this->timeframeLimit) {
            $sleepTime = $this->timeframeSize - (time() % $this->timeframeSize);
            $this->logger->debug('Sleep for ' . number_format($sleepTime, 3) . 's (timeframeCounter)');
            sleep($sleepTime);
        }
        $sleepTime = max(0, ($this->minTimeBetweenCalls + $this->lastCall) - microtime(true));
        $this->logger->debug('Sleep for ' . number_format($sleepTime, 3) . 's (timeBetweenCalls)');
        usleep(1000 * 1000 * $sleepTime);
        $content = file_get_contents($url);
        $this->lastCall = microtime(true);
        return $content;
    }
}

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
     * GolemParserService constructor.
     *
     * @param string $cacheFolder
     * @param LoggerInterface $logger
     */
    public function __construct($cacheFolder, LoggerInterface $logger)
    {
        $this->cacheFolder = $cacheFolder;
        $this->logger = $logger;
    }

    /**
     * @param string $url
     * @return string
     */
    public function loadContent(string $url): string
    {
        return $this->getCached(md5($url), function () use ($url) {
            $fileName = $this->cacheFolder . '/' . md5($url) . '_' . static::CURRENT_VERSION . '.cache';
            if (file_exists($fileName)) {
                $this->logger->debug('Cache hit filesystem cache for url: "' . $url . '" ('.$fileName.')');
                return trim(file_get_contents($fileName));
            }
            $this->logger->debug('Cache miss filesystem cache for url: "' . $url . '" (' . $fileName . ')');

            $result = file_get_contents($url);

            $this->logger->debug('Cache write filesystem cache for url: "' . $url . '" (' . $fileName . ')');
            file_put_contents($fileName, $result);

            return $result;
        });
    }
}

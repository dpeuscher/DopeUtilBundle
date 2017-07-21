<?php

namespace Dope\UtilBundle\Cache;

/**
 * @category  evernote
 * @copyright Copyright (c) 2017 CHECK24 Vergleichsportal Flüge GmbH
 */
interface CalculationCacheInterface
{
    /**
     * @param bool $useCalculationCache
     */
    public function setUseCalculationCache($useCalculationCache);

    /**
     * @return boolean
     */
    public function getUseCalculationCache();

    /**
     * @return bool
     */
    public function isUseCalculationCache();
}

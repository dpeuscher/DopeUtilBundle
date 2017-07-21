<?php

namespace Dope\UtilBundle\Cache;

trait CalculationCacheTrait
{
    /**
     * @var bool
     */
    protected $useCalculationCache = false;

    /**
     * @var mixed[]
     */
    protected $calculationCache = [];

    /**
     * @return boolean
     */
    public function getUseCalculationCache()
    {
        return $this->useCalculationCache;
    }

    /**
     * @return bool
     */
    public function isUseCalculationCache()
    {
        return $this->useCalculationCache;
    }

    /**
     * @param boolean $useCalculationCache
     */
    public function setUseCalculationCache($useCalculationCache)
    {
        if ($useCalculationCache != $this->useCalculationCache) {
            $this->calculationCache = [];
            $this->promoteUseCalculation($useCalculationCache);
        }
        $this->useCalculationCache = $useCalculationCache;
    }

    /**
     * @param bool $value
     */
    protected function promoteUseCalculation($value)
    {
        foreach (get_object_vars($this) as $var) {
            if ($var instanceof CalculationCacheInterface) {
                $var->setUseCalculationCache($value);
            }
        }
    }

    /**
     * @param string $key
     * @param callable $method
     */
    protected function getCached($key, $method)
    {
        if ($this->useCalculationCache) {
            if (!isset($this->calculationCache[$key])) {
                $this->calculationCache[$key] = $method();
            }
            return $this->calculationCache[$key];
        }
        return $method();
    }
}

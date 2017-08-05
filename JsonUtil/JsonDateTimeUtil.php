<?php

namespace Dope\UtilBundle\JsonUtil;

use DateTime;

/**
 * @category  stocks
 * @copyright Copyright (c) 2017 Dominik Peuscher
 */
class JsonDateTimeUtil
{
    public static function json_encode($array, $dateTimeToString = true)
    {
        $stringConfigArray = [];
        foreach ($array as $key => $value) {
            $stringConfigArray[$key] = $value;
            if ($dateTimeToString && $value instanceof DateTime) {
                $stringConfigArray[$key] = $value->format('Y-m-d H:i:s');
            }
        }
        return json_encode($stringConfigArray);
    }
}

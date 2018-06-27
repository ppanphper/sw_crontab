<?php
/**
 * Created by PhpStorm.
 * User: pandy
 * Date: 2018/5/8
 * Time: 下午4:56
 */

namespace app\helpers;

class TimeHelper
{

    /**
     * 秒、毫秒、微秒格式化
     *
     * @param int $time
     *
     * @return string
     */
    public static function msTimeFormat($time)
    {
        if (!is_numeric($time)) return $time;
        if ($time >= 1) {
            return round($time, 2) . "s";
        }
        if ($time >= 0.001) {
            return round(($time * 1000), 2) . "ms";
        }
        if ($time < 0.001) {
            return round(($time * 1000000), 2) . "μs";
        }
        return $time . "s";
    }
}
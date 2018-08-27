<?php
/**
 * Created by PhpStorm.
 * User: pandy
 * Date: 2018/5/8
 * Time: 下午4:56
 */
namespace app\helpers;
use Yii;

class TimeHelper {

    /**
     * 天、小时、分钟、秒、毫秒、微秒格式化
     *
     * @param int $time
     *
     * @return string
     */
    public static function msTimeFormat($time) {
        if (!is_numeric($time)) return $time;
        if($time >= 0.001 && $time < 1) {
            return round(($time * 1000), 2) . 'ms';
        }
        if($time < 0.001) {
            return round(($time * 1000000), 2) . 'μs';
        }
        if($time >= 1 && $time < 60) {
            return round($time, 2).'s';
        }
        if($time >= 60 && $time < 3600) {
            return round($time / 60, 2) . 'm';
        }
        if($time >= 3600 && $time < 86400) {
            return round($time / 3600, 2) . 'h';
        }
        if($time >= 86400) {
            return round($time / 86400, 2) . 'd';
        }
        return $time . "s";
    }
}
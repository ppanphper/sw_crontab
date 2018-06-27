<?php
/**
 * 解析Crontab格式
 * Created by PhpStorm.
 * User: ClownFish 187231450@qq.com
 * Date: 14-12-27
 * Time: 上午11:59
 */

namespace Libs;

class ParseCrontab
{
    public static $error;

    /**
     *  解析crontab的定时格式，linux只支持到分钟/，这个类支持到秒
     *
     * @param string $crontab_string :
     *
     *      0     1    2    3    4    5
     *      *     *    *    *    *    *
     *      -     -    -    -    -    -
     *      |     |    |    |    |    |
     *      |     |    |    |    |    +----- day of week (0 - 6) (Sunday=0)
     *      |     |    |    |    +----- month (1 - 12)
     *      |     |    |    +------- day of month (1 - 31)
     *      |     |    +--------- hour (0 - 23)
     *      |     +----------- min (0 - 59)
     *      +------------- sec (0-59)
     * @param int $start_time timestamp [default=current timestamp]
     *
     * @return array|bool|null unix timestamp - 下一分钟内执行是否需要执行任务，如果需要，则把需要在那几秒执行返回
     */
    public static function parse($crontab_string, $start_time = null)
    {
        if (!preg_match(Constants::CRON_RULE_PATTERN, trim($crontab_string))) {
            self::$error = "Invalid cron string: " . $crontab_string;
            return false;
        }

        if ($start_time && !is_numeric($start_time)) {
            self::$error = "\$start_time must be a valid unix timestamp ($start_time given)";
            return false;
        }
        $cron = preg_split("/[\s]+/i", trim($crontab_string));
        $start = empty($start_time) ? time() : $start_time;
        $cronCount = count($cron);

        // 分钟级
        if ($cronCount == 5) {
            $date = array(
                // 因为是当前一分钟生成下一分钟的任务，所以可以从0秒开始运行
                'second'  => array(0 => 0),
                'minutes' => self::_parse_cron_number($cron[0], 0, 59),
                'hours'   => self::_parse_cron_number($cron[1], 0, 23),
                'day'     => self::_parse_cron_number($cron[2], 1, 31),
                'month'   => self::_parse_cron_number($cron[3], 1, 12),
                'week'    => self::_parse_cron_number($cron[4], 0, 6),
            );
        } // 秒级
        elseif ($cronCount == 6) {
            $date = array(
                'second'  => (empty($cron[0])) ? array(0 => 0) : self::_parse_cron_number($cron[0], 0, 59),
                'minutes' => self::_parse_cron_number($cron[1], 0, 59),
                'hours'   => self::_parse_cron_number($cron[2], 0, 23),
                'day'     => self::_parse_cron_number($cron[3], 1, 31),
                'month'   => self::_parse_cron_number($cron[4], 1, 12),
                'week'    => self::_parse_cron_number($cron[5], 0, 6),
            );
        }
        if (
            !empty($date) &&
            in_array(intval(date('i', $start)), $date['minutes']) &&
            in_array(intval(date('G', $start)), $date['hours']) &&
            in_array(intval(date('j', $start)), $date['day']) &&
            in_array(intval(date('w', $start)), $date['week']) &&
            in_array(intval(date('n', $start)), $date['month'])

        ) {
            return $date['second'];
        }
        return null;
    }

    /**
     * 解析单个配置的含义
     *
     * @param $s
     * @param $min
     * @param $max
     *
     * @return array
     */
    protected static function _parse_cron_number($s, $min, $max)
    {
        $result = array();
        $v1 = explode(",", $s);
        foreach ($v1 as $v2) {
            $v3 = explode("/", $v2);
            $step = (empty($v3[1]) || intval($v3[1]) <= 0) ? 1 : intval($v3[1]);
            $v4 = explode("-", $v3[0]);
            $_min = $v3[0];
            $_max = $v3[0];
            if (count($v4) == 2) {
                $_min = intval($v4[0]);
                $_max = intval($v4[1]);
            } else if ($v3[0] == "*") {
                $_min = $min;
                $_max = $max;
            }
            for ($i = $_min; $i <= $_max; $i += $step) {
                if (intval($i) < $min) {
                    $result[$min] = $min;
                } elseif (intval($i) > $max) {
                    $result[$max] = $max;
                } else {
                    $result[$i] = intval($i);
                }
            }
        }
        ksort($result);
        return $result;
    }
}
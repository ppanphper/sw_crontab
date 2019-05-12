<?php
/**
 * Created by PhpStorm.
 * User: pandy
 * Date: 2018/7/3
 * Time: 上午10:33
 */

namespace app\helpers;
use Yii;
use app\config\Constants;
use \Exception;
use yii\log\Logger;


class CommonHelper
{
    /**
     * 获取、释放分布式锁
     *
     * @param $redisKeySuffix
     * @param int $type 1=lock 0=unlock
     * @param int $expireTime
     *
     * @return mixed
     * @throws Exception
     */
    public static function getConcurrentLock($redisKeySuffix, $type = 1, $expireTime=300) {
        static $serverInternalIp = null;
        if($serverInternalIp === null) {
            $serverInternalIp = self::getServerInternalIp(); // 获取服务器内部IP地址
        }
        if(!is_string($redisKeySuffix)) {
            throw new Exception('key必须是字符串类型');
        }

        if(strlen($redisKeySuffix) != 32) {
            $redisKeySuffix = md5($redisKeySuffix);
        }

        $redisObject = Yii::$app->redis;
        $redisKey = Constants::REDIS_CONCURRENT_FLAG_PREFIX . $redisKeySuffix;
        // 尝试加锁
        if($type == 1) {
            /** 假如连不上Redis，那么所有机器都不运行这个脚本 */
            // 成功返回1，失败返回0; 锁定这个交易所，本次在这台机器上运行
            return $redisObject->set($redisKey, $serverInternalIp, ['NX', 'EX' => $expireTime]);
        }
        // 释放锁
        else {
            return $redisObject->del($redisKey);
        }
    }

    /**
     * 获取服务器内网IP
     * @return string
     */
    public static function getServerInternalIp() {
        static $ip = '';
        if($ip) {
            return $ip;
        }
        $patternArray = array(
            '10\.',
            '172\.1[6-9]\.',
            '172\.2[0-9]\.',
            '172\.31\.',
            '192\.168\.'
        );
        try {
            $pattern = implode('|', $patternArray);
            // 如果有Swoole扩展
            if (extension_loaded('swoole')) {
                $serverIps = swoole_get_local_ip();
                foreach ($serverIps as $serverIp) {
                    // 匹配内网IP
                    if (preg_match('#^' . $pattern . '#', $serverIp)) {
                        $ip = $serverIp;
                        break;
                    }
                }
            } else {
                // LINUX
                if (self::isLinuxOS()) {
                    // 由于文件没有可执行权限，所以无法获取
                    $ip = exec("/sbin/ifconfig|grep -oP '(?<=inet addr:)[^ ]+'|grep -E '^" . $pattern . "'|head -1");
                }
                // OSX
                else if (self::isMacOS()) {
                    $ip = exec("/sbin/ifconfig|grep -Eo 'inet (".$pattern.")[^ ]+|grep -Eo '(" . $pattern . ")[^ ]+'|head -1");
                }
                // WINDOWS
                else if (stristr(PHP_OS, 'WIN')) {
                    exec("ipconfig /all", $ipInfo);
                    foreach ($ipInfo as $line) {
                        // 匹配IPV4
                        if (preg_match('#\s*IPv4[^\d]+(\d+\.\d+\.\d+\.\d+).*#i', $line, $matches)) {
                            if (isset($matches[1])) {
                                $ip = $matches[1];
                                break;
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            Yii::getLogger()->log('获取服务器内网IP失败: ' . $e->getMessage(), Logger::LEVEL_WARNING);
        }
        if(!$ip) {
            // 获取不到内网IP
            $ip = '0.0.0.0';
        }
        return $ip;
    }

    /**
     * 是否是Linux系统
     * @return bool
     */
    public static function isLinuxOS() {
        return stristr(PHP_OS, 'Linux') ? true : false;
    }

    /**
     * 是否是MacOS
     * @return bool
     */
    public static function isMacOS()
    {
        return stristr(PHP_OS, 'DAR') ? true : false;
    }


    /**
     * 字节单位转换
     * @param integer $bytes
     *
     * @return string
     */
    public static function byteConvert($bytes)
    {
        $s = array('B', 'Kb', 'MB', 'GB', 'TB', 'PB');
        if($bytes == 0) {
            return sprintf('%.2f '. $s[0], $bytes);
        }
        $e = floor(log($bytes) / log(1024));

        return sprintf('%.2f ' . $s[$e], ($bytes / pow(1024, $e)));
    }

    /**
     * 输出php错误堆栈
     *
     * @param string $split
     * @param null $exceptionObject
     *
     * @return string
     */
    public static function getBacktrace($split = PHP_EOL, $exceptionObject = null)
    {
        $arr = debug_backtrace();
        return self::convenienceDebug($arr, 0, $split, $exceptionObject);
    }

    /**
     * 获取参数
     *
     * @param mixed $arg
     * @param int $level
     *
     * @return string
     */
    private static function getArg(&$arg, $level = 0)
    {
        // 最大层数
        if ($level >= TRACE_LEVEL) {
            return json_encode($arg);
        }
        if (is_object($arg)) {
            $arr = (array)$arg;
            $args = array();
            foreach ($arr as $key => $value) {
                if (strpos($key, chr(0)) !== false) {
                    $key = '';    // Private variable found
                }
                $level++;
                $args[] = '[' . $key . '] => ' . self::getArg($value, $level);
            }

            $arg = get_class($arg) . ' Object (' . implode(',', $args) . ')';
        }
        return $arg;
    }

    /**
     * 遍历错误堆栈
     *
     * @param $traces
     * @param int $traces_to_ignore
     * @param string $split
     * @param null $exceptionObject
     *
     * @return string
     */
    private static function convenienceDebug($traces, $traces_to_ignore = 0, $split = PHP_EOL, $exceptionObject = null)
    {
        if (($exceptionObject instanceof Exception || $exceptionObject instanceof \Throwable)) {
            $traces = $exceptionObject->getTrace();
        }
        // 如果没有设置Trace的层数
        if (!defined("TRACE_LEVEL")) define("TRACE_LEVEL", 3);
        // 是否显示详细的堆栈
        if (!defined("DEBUG_TRACE_DETAIL")) define("DEBUG_TRACE_DETAIL", TRUE);
        // 堆栈明细参数长度限制
        if (!defined("DEBUG_TRACE_DETAIL_PARAM_LENGTH")) define("DEBUG_TRACE_DETAIL_PARAM_LENGTH", 1024);
        $result = array();
        $count = 0;
        foreach ($traces as $i => $trace) {
            if ($i < $traces_to_ignore) {
                continue;
            }
            // 取得函数或调用方法名称
            $object = isset($trace['class']) ? $trace['class'] . $trace['type'] : '';
            // 序号
            $msg = '[#' . ($i - $traces_to_ignore) . '] ' . $object . $trace['function'];
            // 处理对象的参数
            if (isset($trace['class'])) {
                // 是否显示堆栈明细
                if (DEBUG_TRACE_DETAIL && is_array($trace['args'])) {
                    foreach ($trace['args'] as &$arg) {
                        self::getArg($arg);
                    }
                }
            }
            // 处理换行符
            if (DEBUG_TRACE_DETAIL && isset($trace['args'][0]) && is_string($trace['args'][0]) && in_array(strtolower($trace['args'][0]), array('<br/>', PHP_EOL, '<br>', '</br>'))) {
                $trace['args'][0] = '';
            }
            // 调用的方法与参数
            if (DEBUG_TRACE_DETAIL) {
                $args = empty($trace['args']) ? '' : json_encode($trace['args']);
                $args = mb_strlen($args, 'UTF-8') > DEBUG_TRACE_DETAIL_PARAM_LENGTH ? mb_substr($args, 0, DEBUG_TRACE_DETAIL_PARAM_LENGTH, 'UTF-8') . '...' : $args;
                $msg .= '(' . $args . ')';
            } else {
                $msg .= '()';
            }
            if (isset($trace['file'], $trace['line'])) {
                $msg .= ' in ' . $trace['file'] . '(' . $trace['line'] . ')';
            }
            $result[] = $msg;
            if (++$count >= TRACE_LEVEL)
                break;
        }
        $str = implode($split, $result) . $split . $split . '--------------------------------------------------------------------------------' . $split;
        return $str;
    }
}
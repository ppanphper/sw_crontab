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
                else if (stristr(PHP_OS, 'DAR')) {
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
}
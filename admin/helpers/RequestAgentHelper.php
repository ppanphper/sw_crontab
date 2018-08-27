<?php
/**
 * Created by PhpStorm.
 * User: pandy
 * Date: 2018/7/3
 * Time: 上午10:16
 */

namespace app\helpers;
use app\helpers\PacketHelper as Packet;
use function Libs\log_warning;
use \Swoole\Client as SwooleClient;
use app\config\Constants;

class RequestAgentHelper
{
    /**
     * 控制请求
     *
     * @param string $name
     * @param array $params
     * @param $ip
     * @param $port
     *
     * @return array
     */
    public static function controlCommand($name, array $params, $ip, $port) {
        $data = [
            'cmd' => $name,
            'param' => $params,
            'type' => Constants::SW_CONTROL_CMD,
        ];
        return RequestAgentHelper::tcpRequest($ip, $port, $data);
    }

    /**
     * TCP请求
     *
     * @param $ip
     * @param $port
     * @param $sendData
     *
     * @return array
     */
    public static function tcpRequest($ip, $port, $sendData) {
        $client = new SwooleClient(SWOOLE_SOCK_TCP | SWOOLE_KEEP);
        if (!$client->connect($ip, $port, 2)) {
            //connect fail
            $errorCode = $client->errCode;
            if ($errorCode == 0) {
                $msg = "connect fail, check host dns; address = " . $ip . ':' . $port;
                $errorCode = -1;
            } else {
                $msg = 'connect fail, '. socket_strerror($errorCode) . '; address = ' . $ip . ':' . $port;
            }
            log_warning(__METHOD__ . ' '.$msg);
            return Packet::packFormat($msg, $errorCode);
        }
        if(!is_string($sendData)) {
            $sendData = Packet::packEncode($sendData);
        }
        $bool = $client->send($sendData);

        // 发送失败
        if (!$bool) {
            $errorCode = $client->errCode;

            if ($errorCode == 0) {
                $msg = "send fail, check host dns; address = " . $ip . ':' . $port;
                $errorCode = -1;
            } else {
                $msg = 'send fail, '. socket_strerror($errorCode) . '; address = ' . $ip . ':' . $port;
            }

            return Packet::packFormat($msg, $errorCode);
        }

        $result = $client->recv();
        // 连接关闭返回空字符串
        // 失败返回 false，并设置$swoole_client->errCode属性
        if (!empty($result)) {
            return Packet::packDecode($result);
        } else {
            $msg = ' Error message = ' . ($result === FALSE ? socket_strerror($client->errCode) : 'connection is closed. result = ' . var_export($result, true));
            return Packet::packFormat("The receive wrong or timeout.{$msg}", Constants::STATUS_CODE_RECEIVE_WRONG_OR_TIMEOUT);
        }
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: pandy
 * Date: 2017/1/19
 * Time: 15:37
 */

namespace Libs;

use \Swoole\Channel as SwooleChannel;

//use \Swoole\Coroutine\Channel as SwooleChannel;

class LogChannel
{
    private static $_instance = null;
    private $_channel = null;

    public function __construct($channelSize = null)
    {
        if (empty($channelSize)) {
            $channelSize = config_item('channel_size', 1024 * 1024 * 512);
        }
        $this->_channel = new SwooleChannel($channelSize);
    }

    /**
     * @return null|LogChannel
     */
    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function stats()
    {
        return $this->_channel->stats();
    }

    public function __call($method, $arguments)
    {
        return $this->_channel->{$method}(...$arguments);
    }

    private function __clone()
    {
    }
}
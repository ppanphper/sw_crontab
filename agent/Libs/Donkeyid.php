<?php

namespace Libs;

use \Swoole\Table as SwooleTable;
use \Swoole\Lock as SwooleLock;

/**
 * 获取唯一id
 * Created by PhpStorm.
 * User: ClownFish
 * Email: 187231450#qq.com
 * Date: 16-8-5
 * Time: 下午2:14
 */
class Donkeyid
{
    static $donkeyid;

    private $node_id;
    private $epoch;
    private $table;
    private $lock;

    const snowflake = 0;
    const TIMESTAMP_BITS = 42;
    const NODE_ID_BITS = 12;
    const SEQUENCE_BITS = 9;
    const TIMESTAMP_LEFT_SHIFT = 21;
    const NODE_ID_LEFT_SHIFT = 9;

    public function __construct($node_id = false, $epoch = false)
    {
        /**
         * [DonkeyId]
         * ;0-4095
         * donkeyid.node_id=0
         * ;0-当前时间戳
         * donkeyid.epoch=0
         */
        if ($node_id === false) {
            $node_id = ini_get("donkeyid.node_id");
        }
        if ($epoch === false) {
            $epoch = ini_get("donkeyid.epoch");
        }
        $this->node_id = ($node_id == false || $node_id < 0) ? 0 : $node_id;
        $this->epoch = ($epoch == false || $epoch < 0) ? 0 : $epoch;
        $this->create_table();
    }

    static function init()
    {
        self::getInstance();
    }

    static function getInstance()
    {
        if (!self::$donkeyid) {
            // 根据机器内网IP得出节点Id
            $node_id = ip2long(getServerInternalIp()) % 4096;
            self::$donkeyid = new Donkeyid($node_id);
        }
        return self::$donkeyid;
    }

    /**
     * 创建共享内存
     */
    private function create_table()
    {
        $this->table = new SwooleTable(3);
        $this->table->column("last_timestamp", \swoole_table::TYPE_INT, 8);
        $this->table->column("sequence", \swoole_table::TYPE_INT, 4);
        $this->table->create();
        $this->lock = new SwooleLock(SWOOLE_SPINLOCK);
    }

    /**
     * 获取当前毫秒
     * @return int
     */
    private function get_curr_timestamp_ms()
    {
        return (int)(microtime(true) * 1000);
    }

    /**
     * 暂停一毫秒
     * @return int
     */
    private function wait_next_ms()
    {
        usleep(1000);
        return $this->get_curr_timestamp_ms();
    }

    /**
     * 获取id
     * @return int
     */
    public function dk_get_next_id()
    {
        $now = $this->get_curr_timestamp_ms();
        $isMacOS = isMacOS();
        // MacOS Mojave使用锁会异常退出
        if (!$isMacOS) {
            $this->lock->lock();
        }
        $col = $this->table->get(self::snowflake);
        if ($col == false || $col["last_timestamp"] > $now) {
            $last_timestamp = $now;
            $sequence = mt_rand(0, 10) % 2;
        } else {
            $last_timestamp = $col["last_timestamp"];
            $sequence = $col["sequence"];
        }
        if ($now == $last_timestamp) {
            $sequence = ($sequence + 1) & ((-1 ^ (-1 << self::SEQUENCE_BITS)));
            if ($sequence == 0) {
                $now = $this->wait_next_ms();
            }
        }
        $this->table->set(self::snowflake, array("last_timestamp" => $now, "sequence" => $sequence));
        // MacOS Mojave使用锁会异常退出
        if (!$isMacOS) {
            $this->lock->unlock();
        }
        $id = (($now - ($this->epoch * 1000) & (-1 ^ (-1 << self::TIMESTAMP_BITS))) << self::TIMESTAMP_LEFT_SHIFT)
            | (($this->node_id & (-1 ^ (-1 << self::NODE_ID_BITS))) << self::NODE_ID_LEFT_SHIFT)
            | ($sequence);
        return $id;
    }

    /**
     * 解析id，获取id的组成元素
     *
     * @param $id
     *
     * @return mixed
     */
    public function dk_parse_id($id)
    {
        $ret["time"] = ($id >> self::TIMESTAMP_LEFT_SHIFT) + ($this->epoch * 1000);
        $ret["node_id"] = ($id >> self::NODE_ID_LEFT_SHIFT) & (-1 ^ (-1 << self::NODE_ID_BITS));
        $ret["sequence"] = $id & (-1 ^ (-1 << self::SEQUENCE_BITS));
        return $ret;
    }
}
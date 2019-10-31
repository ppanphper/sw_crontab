<?php

namespace Models;

/**
 * This is the model class for table "agents".
 *
 * @property integer $id
 * @property string $name
 * @property string $ip
 * @property integer $port
 * @property integer $status
 * @property integer $last_report_time
 * @property integer $agent_status
 */
class Agents extends DB
{
    const STATUS_DISABLED = 0; // 停用
    const STATUS_ENABLED = 1; // 启用

    const AGENT_STATUS_OFFLINE = 0; // 离线
    const AGENT_STATUS_ONLINE = 1; // 在线
    const AGENT_STATUS_ONLINE_REPORT_FAILED = 2; // 在线, 但是Redis没有上报

    /**
     * 记录本机agent信息
     *
     * @var array
     */
    private static $_agentInfo = [];

    /**
     * 更新信息标记
     *
     * @var bool
     */
    private static $_updateFlag = false;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'agents';
    }

    /**
     * 获取节点信息
     * @param bool $reload 是否强制重新加载
     * @return array|mixed
     */
    public static function getInfo($reload = false) {
        if (!self::$_agentInfo || $reload || self::$_updateFlag) {
            try {
                // 查询本节点的信息
                // TODO 把节点信息缓存到redis，更新的时候一起更新redis信息
                self::$_agentInfo = self::getInstance()->from(self::tableName())->where([
                    'ip'     => SERVER_INTERNAL_IP,
                    'port'   => SERVER_PORT,
                    // 自己判断是否停用，以免未查到数据而误以为停用
//                    'status' => self::STATUS_ENABLED,
                ])->limit(1)->fetch();
            } catch (\Exception $e) {
            }
        }
        return self::$_agentInfo;
    }

    /**
     * 节点是否已停用
     * @return bool
     */
    public static function isDisabled() {
        $bool = false;
        if (self::$_agentInfo && self::$_agentInfo['status'] != self::STATUS_ENABLED) {
            $bool = true;
        }
        return $bool;
    }

    /**
     * 设置更新信息标记
     * @param bool $flag true|false
     *
     * @return bool
     */
    public static function setUpdateFlag($flag) {
        self::$_updateFlag = $flag;
        return self::$_updateFlag;
    }

    /**
     * 是否更新了节点信息
     * @return bool
     */
    public static function isUpdated() {
        return self::$_updateFlag;
    }
}

<?php

/**
 * task任务的管理类
 * Created by PhpStorm.
 * User: liuzhiming
 * Date: 16-8-18
 * Time: 下午5:44
 */

namespace Libs;

use Models\Agents;
use Models\Crontab;
use \Swoole\Table as SwooleTable;

class LoadTasks
{
    /**
     * TYPE_INT 1(Mysql TINYINT): 2 ^ 8 = -128 ~ 127
     * TYPE_INT 2(Mysql SMALLINT): 2 ^ (8 * 2) = -327689 ~ 32767
     * TYPE_INT 4(Mysql INT): 2 ^ (8 * 4) = -2147483648 ~ 2147483647
     * TYPE_INT 8(Mysql BIGINT): 2 ^ (8 * 8) = -9223372036854775808 ~ 9223372036854775807
     * @var array
     */
    private static $_column = [
        'name'          => [SwooleTable::TYPE_STRING, 64], // crontab名称
        'rule'          => [SwooleTable::TYPE_STRING, 1800], // crontab规则
        'execNum'       => [SwooleTable::TYPE_INT, 2], // 并发数
        'maxTime'       => [SwooleTable::TYPE_INT, 4], // 最大执行时间
        'timeoutOpt'    => [SwooleTable::TYPE_INT, 1], // 超时选项 0=忽略 1=强杀
        'logOpt'        => [SwooleTable::TYPE_INT, 1], // 日志选项 0=忽略 1=按运行Id生成日志文件并写入
        'runUser'       => [SwooleTable::TYPE_STRING, 255], // 运行时用户
        'command'       => [SwooleTable::TYPE_STRING, 1536], // 命令 长度为字节 512 * 3
        'retries'       => [SwooleTable::TYPE_INT, 2], // 重试次数
        'retryInterval' => [SwooleTable::TYPE_INT, 4], // 重试间隔
        'noticeWay'     => [SwooleTable::TYPE_INT, 1], // 通知方式 0忽略 1邮件 2短信 3邮件+短信 4微信 5邮件+微信 6短信+微信 7所有方式
        'updateTime'    => [SwooleTable::TYPE_INT, 4], // 上一次更新时间
    ];
    private static $_table;

    /**
     * 记录本机agent信息
     *
     * @var array
     */
    private static $_agentInfo = [];

    /**
     * 上一次变更的md5值，如果与新的md5值不一致，就重新加载数据到内存表
     *
     * @var string
     */
    public static $crontabMD5 = '';

    const T_START = 1;//正常
    const T_STOP = 0;//暂停

    const RUN_STATUS_ERROR = -1;//不符合条件，不运行
    const RUN_STATUS_NORMAL = 0;//准备运行
    const RUN_STATUS_START = 1;//开始运行
    const RUN_STATUS_CREATE_PROCESS_SUCCESS = 2;//创建进程成功
    const RUN_STATUS_CREATE_PROCESS_FAILED = 3;//创建进程失败
    const RUN_STATUS_SUCCESS = 4;//运行成功
    const RUN_STATUS_FAILED = 5;//运行失败
    const RUN_STATUS_RETIRES = 6;//重试

    /**
     * 初始化任务表
     */
    public static function init()
    {
        //创建config table
        self::createConfigTable();
    }

    /**
     * 创建配置表
     */
    private static function createConfigTable()
    {
        self::$_table = new SwooleTable(TASK_MAX_LOAD_SIZE);
        foreach (self::$_column as $key => $v) {
            self::$_table->column($key, $v[0], $v[1]);
        }
        self::$_table->create();
    }


    /**
     * 载入任务
     *
     * @param array $loadSpecifiedIds 加载指定任务Id记录
     *
     * @return bool
     * @throws \Exception
     */
    public static function load($loadSpecifiedIds = [])
    {
        $redisObject = RedisClient::getInstance();
        // 获取节点信息，如果发现节点已停用，就删除指定本节点执行的任务，只执行未指定节点并且没有禁止本节点执行的任务
        $agentInfo = Agents::getInfo();
        if (Agents::isUpdated()) {
            // 节点已停用，需要把所有的任务都删除，此节点不再运行任务
            if (Agents::isDisabled() && count(self::$_table) > 0) {
                foreach (self::$_table as $taskId => $task) {
                    self::$_table->del($taskId);
                }
            }
            Agents::setUpdateFlag(false);
            // 删除已经处理的通知
            $redisObject->del(Constants::REDIS_KEY_AGENT_CHANGE_MD5 . SERVER_INTERNAL_IP . '_' . SERVER_PORT);
        }
        $offset = 0;
        $limit = 1000;
        $agentId = isset($agentInfo['id']) ? $agentInfo['id'] : 0;
        $keepLoop = true;
        while ($keepLoop) {
            // 获取当前节点需要执行的任务数据
            $tasks = Crontab::getCurrentNodeTaskData($agentId, $loadSpecifiedIds, Crontab::STATUS_ENABLED, $offset, $limit);
            if (empty($tasks)) break;
            foreach ($tasks as $task) {

                if (count(self::$_table) > TASK_MAX_LOAD_SIZE) {
                    // 达到最大加载任务数，就终止加载
                    $keepLoop = false;
                    break;
                }

                self::$_table->set($task['id'], [
                    'name'          => $task['name'],
                    'rule'          => $task['rule'],
                    'execNum'       => $task['concurrency'],
                    'maxTime'       => $task['max_process_time'],
                    'timeoutOpt'    => $task['timeout_opt'],
                    'logOpt'        => $task['log_opt'],
                    'runUser'       => $task['run_user'],
                    'command'       => $task['command'],
                    'retries'       => $task['retries'],
                    'retryInterval' => $task['retry_interval'],
                    'noticeWay'     => $task['notice_way'],
                    'updateTime'    => $task['update_time'],
                ]);
            }
            $offset += $limit;
        }

        // 更新md5值
        self::$crontabMD5 = $redisObject->get(Constants::REDIS_KEY_CRONTAB_CHANGE_MD5);
        return true;
    }

    /**
     * 监听数据库是否有变更，如果变更，就重新加载任务到内存表
     */
    public static function monitorReload()
    {
        $redisObject = RedisClient::getInstance();
        // 获取节点信息是否有变更
        $updateAgentInfoFlag = $redisObject->get(Constants::REDIS_KEY_AGENT_CHANGE_MD5 . SERVER_INTERNAL_IP . '_' . SERVER_PORT);
        Agents::setUpdateFlag($updateAgentInfoFlag);
        try {
            /**
             * 如果节点状态有变更(停用、删除)就清除所有任务，并重新加载一次(只加载未指定节点执行的任务)
             */
            if ($updateAgentInfoFlag) {
                return self::load();
            }
            $md5 = $redisObject->get(Constants::REDIS_KEY_CRONTAB_CHANGE_MD5);
            if ($md5 !== false && self::$crontabMD5 !== $md5 && ($data = $redisObject->hGetAll(Constants::REDIS_KEY_HASH_CRONTAB_CHANGE))) {
                $ids = $hashDelFields = [];
                // 如果域记录时间与当前时间相差10分钟，就删除，以免无效记录过多
                $invalidRecordTime = time() - Constants::REDIS_KEY_HASH_CRONTAB_CHANGE_FIELD_EXPIRE;
                foreach ($data as $id => $updateTime) {
                    $prevUpdateTime = self::$_table->get($id, 'updateTime');
                    // 如果不存在这个任务，或者更新时间大于上次更新时间
                    if (!$prevUpdateTime || $updateTime > $prevUpdateTime) {
                        $ids[] = $id;
                        self::$_table->del($id);
                    }
                    if ($invalidRecordTime >= $updateTime) {
                        $hashDelFields[] = $id;
                    }
                }
                // 重新加载指定Id记录
                if ($ids) {
                    try {
                        self::load($ids);
                    } catch (\Exception $e) {
                        logWarning(__METHOD__ . ' Error = ' . $e->getMessage());
                    }
                }
                // 删除过期域记录
                if ($hashDelFields) {
                    array_unshift($hashDelFields, Constants::REDIS_KEY_HASH_CRONTAB_CHANGE);
                    // call_user_func_array([$redisObject, 'hdel'], $hashDelFields);
                    $redisObject->hdel(...$hashDelFields);
                }
            }
        } catch (\Exception $e) {
            logWarning(__METHOD__ . ' Error = ' . $e->getMessage());
        }
    }

    /**
     * 获取table内存表对象
     *
     * @return array
     */
    public static function getTable()
    {
        return self::$_table;
    }

    public static function merge_spaces($string)
    {
        return preg_replace('/\s(?=\s)/', '\\1', $string);
    }

    /**
     * 获取节点名称
     * 此处不作查询逻辑，防止因为查询导致异步进程受影响
     *
     * @return string
     */
    public static function getAgentName()
    {
        $agentName = SERVER_INTERNAL_IP . ':' . SERVER_PORT;
        if (self::$_agentInfo) {
            $agentName = SERVER_INTERNAL_IP . ':' . SERVER_PORT . '(' . self::$_agentInfo['name'] . ')';
        }
        return $agentName;
    }
}
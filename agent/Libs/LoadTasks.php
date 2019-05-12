<?php

/**
 * task任务的管理类
 * Created by PhpStorm.
 * User: liuzhiming
 * Date: 16-8-18
 * Time: 下午5:44
 */

namespace Libs;

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
    private static $column = [
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
    private static $table;

    /**
     * 上一次变更的md5值，如果与新的md5值不一致，就重新加载数据到内存表
     *
     * @var string
     */
    public static $crontabMD5 = '';

    /**
     * 记录本机agent信息
     *
     * @var array
     */
    private static $agentInfo = [];
    private static $updateAgentInfo = false;

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
        self::$table = new SwooleTable(TASK_MAX_LOAD_SIZE);
        foreach (self::$column as $key => $v) {
            self::$table->column($key, $v[0], $v[1]);
        }
        self::$table->create();
    }


    /**
     * 载入任务
     *
     * @param array $ids 加载指定任务Id记录
     *
     * @return bool
     * @throws \Exception
     */
    public static function load($ids = [])
    {
        $db = getDBInstance();
        // 获取节点信息
        self::getAgentInfo();
        $offset = 0;
        $limit = 1000;
        $condition = 'a.status = :status AND (b.bid = :agentId OR b.bid IS NULL) AND a.id NOT IN (SELECT aid FROM via_table WHERE `type` = :notInAgents AND bid = :notInAgentId)';
        $agentId = isset(self::$agentInfo['id']) ? self::$agentInfo['id'] : 0;
        $params = [
            ':status'       => self::T_START,
            ':agentId'      => $agentId,
            ':notInAgents'  => Constants::TYPE_CRONTAB_NOT_IN_AGENTS,
            ':notInAgentId' => $agentId,
        ];
        while (true) {
            /**
             * SELECT DISTINCT a.id,a.* FROM crontab a
             * LEFT JOIN via_table b ON a.id = b.aid AND b.type = 2
             * WHERE (b.bid = 1 OR b.bid IS NULL)
             * AND a.id NOT IN (SELECT aid FROM via_table WHERE `type` = 3 AND bid = 1)
             */
            $query = $db->from('crontab a')
                ->select(null)
                ->select('DISTINCT (a.id), a.*')
                ->leftJoin('via_table b ON a.id = b.aid AND b.type = ' . Constants::TYPE_CRONTAB_AGENTS);
            if ($ids) {
                $query->where('a.id', $ids);
            }
            $tasks = $query->where($condition, $params)
                ->offset($offset)
                ->limit($limit)
                ->fetchAll();
            if (empty($tasks)) break;
            foreach ($tasks as $task) {
                if (count(self::$table) <= TASK_MAX_LOAD_SIZE) {
                    self::$table->set($task['id'], [
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
            }
            $offset += 1000;
        }

        $redisObject = RedisClient::getInstance();
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
        // 节点信息是否有变更
        self::$updateAgentInfo = $redisObject->get(Constants::REDIS_KEY_AGENT_CHANGE_MD5 . SERVER_INTERNAL_IP . '_' . SERVER_PORT);
        try {
            /**
             * 如果节点状态(停用、删除)有变更就清除所有任务，并重新加载一次(只加载未指定节点执行的任务)
             */
            if (self::$updateAgentInfo) {
                return self::load();
            }
            $md5 = $redisObject->get(Constants::REDIS_KEY_CRONTAB_CHANGE_MD5);
            if ($md5 !== false && self::$crontabMD5 !== $md5 && ($data = $redisObject->hGetAll(Constants::REDIS_KEY_HASH_CRONTAB_CHANGE))) {
                $ids = $hashDelFields = [];
                // 如果域记录时间与当前时间相差10分钟，就删除，以免无效记录过多
                $invalidRecordTime = time() - Constants::REDIS_KEY_HASH_CRONTAB_CHANGE_FIELD_EXPIRE;
                foreach ($data as $id => $updateTime) {
                    $prevUpdateTime = self::$table->get($id, 'updateTime');
                    // 如果不存在这个任务，或者更新时间大于上次更新时间
                    if (!$prevUpdateTime || $updateTime > $prevUpdateTime) {
                        $ids[] = $id;
                        self::$table->del($id);
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
        return self::$table;
    }

    public static function merge_spaces($string)
    {
        return preg_replace('/\s(?=\s)/', '\\1', $string);
    }

    /**
     * 获取节点信息
     *
     * @return array|mixed
     */
    public static function getAgentInfo()
    {
        if (empty(self::$agentInfo) || self::$updateAgentInfo) {
            try {
                $db = getDBInstance();
                // 查询本节点的信息
                self::$agentInfo = $db->from('agents')->where([
                    'ip'     => SERVER_INTERNAL_IP,
                    'port'   => SERVER_PORT,
                    'status' => 1,
                ])->limit(1)->fetch();
                if (self::$updateAgentInfo) {
                    $redisObject = RedisClient::getInstance();
                    // 删除已经处理的通知
                    $redisObject->del(Constants::REDIS_KEY_AGENT_CHANGE_MD5 . SERVER_INTERNAL_IP . '_' . SERVER_PORT);
                    self::$updateAgentInfo = false;
                    // 节点已停用，需要把所有的任务都删除, 然后再加载没有指定节点执行的任务
                    if (!self::$agentInfo && count(self::$table) > 0) {
                        foreach (self::$table as $taskId => $task) {
                            self::$table->del($taskId);
                        }
                    }
                }
            } catch (\Exception $e) {
                logError(__METHOD__ . ' Error: ' . $e->getMessage());
            }
        }
        return self::$agentInfo;
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
        if (self::$agentInfo) {
            $agentName = SERVER_INTERNAL_IP . ':' . SERVER_PORT . '(' . self::$agentInfo['name'] . ')';
        }
        return $agentName;
    }
}
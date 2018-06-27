<?php

/**
 * task任务的管理类
 * Created by PhpStorm.
 * User: liuzhiming
 * Date: 16-8-18
 * Time: 下午5:44
 */

namespace Libs;

use \Swoole\Table;

class LoadTasks
{
    private static $column = [
        'name'      => [Table::TYPE_STRING, 64], // crontab名称
        'rule'      => [Table::TYPE_STRING, 1800], // crontab规则
        'execNum'   => [Table::TYPE_INT, 1], // 并发数
        'maxTime'   => [Table::TYPE_INT, 8], // 最大执行时间
        'status'    => [Table::TYPE_INT, 1], // 状态 0=停用 1=启用
        'runUser'   => [Table::TYPE_STRING, 255], // 运行时用户
        'command'   => [Table::TYPE_STRING, 1536], // 命令 长度为字节 512 * 3
        'owner'     => [Table::TYPE_STRING, 255], // 指定负责人Id字符串列表 eg:1,2,3
        'agents'    => [Table::TYPE_STRING, 255], // 指定代理机器Id字符串列表 eg:1,2,3
        'noticeWay' => [Table::TYPE_INT, 1], // 通知方式 1邮件 2短信 3邮件+短信 4微信 5邮件+微信 6短信+微信 7所有方式
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

    /**
     * @var int
     */
    private static $counter = 0;
    private static $counterMax;

    const T_START = 1;//正常
    const T_STOP = 0;//暂停

    const RunStatusError = -1;//不符合条件，不运行
    const RunStatusNormal = 0;//准备运行
    const RunStatusStart = 1;//开始运行
    const RunStatusCreateProcessSuccess = 2;//创建进程成功
    const RunStatusCreateProcessFailed = 3;//创建进程失败
    const RunStatusSuccess = 4;//运行成功
    const RunStatusFailed = 5;//运行失败

    /**
     * 初始化任务表
     */
    public static function init()
    {
        //创建config table
        self::createConfigTable();
        // 10分钟重新加载一次
        self::$counterMax = intval(bcdiv(config_item('task_reload_interval', 600), config_item('monitor_reload_interval', 10)));
    }

    /**
     * 创建配置表
     */
    private static function createConfigTable()
    {
        self::$table = new Table(TASK_MAX_LOAD_SIZE);
        foreach (self::$column as $key => $v) {
            self::$table->column($key, $v[0], $v[1]);
        }
        self::$table->create();
    }


    /**
     * 载入任务
     *
     * @param array $taskIds
     *
     * @return bool
     * @throws \Exception
     */
    public static function load($taskIds = [])
    {
        $db = getDBInstance();
        $offset = 0;
        $limit = 1000;
        if (empty(self::$agentInfo)) {
            // 查询本节点的信息
            self::$agentInfo = $db->from('agents')->select('id')->where([
                'ip'     => SERVER_INTERNAL_IP,
                'port'   => SERVER_PORT,
                'status' => 1,
            ])->limit(1)->fetch();
        }

        $condition = [];
        if ($taskIds) {
            $condition['id'] = $taskIds;
        }
        while (true) {
            $tasks = $db->from('crontab')->where($condition)->offset($offset)->limit($limit)->fetchAll();
            if (empty($tasks)) break;
            foreach ($tasks as $task) {
                /**
                 * 如果没有查询到，那么就是没有登记，则只处理没有特殊指定哪台机器执行的任务
                 * 如果该机器Id不在指定列表中，则跳过
                 *
                 * 防止在重新加载的时候本次查询db失败，导致本节点无法执行任务
                 */
                if (!empty($task['agents'])) {
                    if (empty(self::$agentInfo) || !in_array(self::$agentInfo['id'], explode(',', $task['agents']))) {
                        // 如果不在允许执行的节点列表中，要判断目前内存表是否有这条记录，如果有，就删除掉
                        self::$table->del($task['id']);
                        continue;
                    }
                }
                // 如果不是启用状态，就删除这条记录
                if ($task['status'] != self::T_START) {
                    self::$table->del($task['id']);
                } else if (count(self::$table) <= TASK_MAX_LOAD_SIZE) {
                    self::$table->set($task['id'], [
                        'name'      => $task['name'],
                        'rule'      => $task['rule'],
                        'cid'       => $task['cid'],
                        'execNum'   => $task['concurrency'],
                        'maxTime'   => $task['max_process_time'],
                        'status'    => $task['status'],
                        'runUser'   => $task['run_user'],
                        'command'   => $task['command'],
                        'owner'     => $task['owner'],
                        'agents'    => $task['agents'],
                        'noticeWay' => $task['notice_way'],
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
        try {
            $redisObject = RedisClient::getInstance();
            $md5 = $redisObject->get(Constants::REDIS_KEY_CRONTAB_CHANGE_MD5);
            /**
             * 如果 数据有变更 或者 每间隔1小时 就重新加载一次
             */
            if (($md5 !== false && self::$crontabMD5 !== $md5) || self::$counter > self::$counterMax) {
                self::load();
                self::$counter = 0;
            }

            self::$counter++;
        } catch (\Exception $e) {
            Server::formatOutput(__METHOD__ .' 监控重载失败 = '.$e->getMessage());
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
}
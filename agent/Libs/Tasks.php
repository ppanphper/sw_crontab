<?php
/**
 * 管理需要处理的任务
 * Created by PhpStorm.
 * User: liuzhiming
 * Date: 16-8-19
 * Time: 下午4:33
 */

namespace Libs;

use \Swoole\Table as SwooleTable;

class Tasks
{
    public static $table;

    private static $column = [
        "minute"    => [SwooleTable::TYPE_INT, 8], // 分钟
        "sec"       => [SwooleTable::TYPE_INT, 8], // 哪一秒执行
        "id"        => [SwooleTable::TYPE_INT, 8], // crontab Id
        "runId"     => [SwooleTable::TYPE_INT, 8],
        "runStatus" => [SwooleTable::TYPE_INT, 1],
    ];

    /**
     * 创建配置表
     */
    public static function init()
    {
        self::$table = new SwooleTable(TASKS_MAX_CONCURRENT_SIZE);
        foreach (self::$column as $key => $v) {
            self::$table->column($key, $v[0], $v[1]);
        }
        self::$table->create();
    }

    /**
     * 每分钟执行一次，产生下一分钟需要执行的任务
     *
     * @return bool
     */
    public static function generateTask()
    {
        $loadTaskTable = LoadTasks::getTable();
        if (count($loadTaskTable) > 0) {
            // 下一分钟时间
            $minute = date('YmdHi', strtotime('+1 minutes'));
            $time = strtotime($minute);
            foreach ($loadTaskTable as $id => $task) {
                if ($task["status"] != LoadTasks::T_START) continue;
                // 解析crontab规则
                $ret = ParseCrontab::parse($task["rule"], $time);
                if ($ret === false) {
                    log_error(ParseCrontab::$error);
                    continue;
                }
                if (empty($ret)) {
                    continue;
                }

                foreach ($ret as $sec) {
                    // 生成每个任务的运行Id
                    $runId = Donkeyid::getInstance()->dk_get_next_id();
                    // 插入到任务内存表
                    self::$table->set($runId, [
                        "minute"    => $minute,
                        "sec"       => $time + $sec,
                        "id"        => $id,
                        "runStatus" => LoadTasks::RunStatusNormal
                    ]);
                }
            }
        }
        self::clean();
        return true;
    }

    /**
     * 清理已执行过的任务
     */
    private static function clean()
    {
        $ids = [];
        $ids2 = [];
        if (count(self::$table) > 0) {
            // 超时则把运行中的数量-1
            $loadTasks = LoadTasks::getTable();
            $currentTime = time();
            foreach (self::$table as $runId => $task) {
                // 如果任务已经被删除
                $maxTime = $loadTasks->get($task['id'], 'maxTime');
                // key不存在会返回false
                if ($maxTime === false) {
                    $ids[] = $runId;// runId
                    continue;
                }
                $maxTime = intval($maxTime);
                // 当前时间小于任务执行时间则跳过
                if ($currentTime < $task['sec']) {
                    continue;
                }
                if ($task["runStatus"] == LoadTasks::RunStatusSuccess || $task["runStatus"] == LoadTasks::RunStatusFailed) {
                    $ids[] = $runId;// runId
                    continue;
                }
                // 超时
                if ($maxTime > 0 && ($currentTime - $task['sec']) > $maxTime) {
                    $ids[] = $runId; // runId
                    if ($task["runStatus"] == LoadTasks::RunStatusStart
                        || $task["runStatus"] == LoadTasks::RunStatusCreateProcessSuccess
                        || $task["runStatus"] == LoadTasks::RunStatusError) {
                        if (empty($task['runId'])) {
                            $task['runId'] = $runId;
                        }
                        $task['maxTime'] = $maxTime;
                        $ids2[] = $task;
                    }
                }
            }
            if ($ids) {
                // 删除
                foreach ($ids as $runId) {
                    self::$table->del($runId);
                }
            }
            if ($ids2) {
                $redisObject = RedisClient::getInstance();
                $dateFormat = config_item('default_date_format', 'Y-m-d H:i:s');
                foreach ($ids2 as $item) {
                    $msg = '指定执行时间: ' . date($dateFormat, $item['sec']);
                    DbLog::log($item['runId'], $item['id'], Constants::CUSTOM_CODE_EXEC_TIMEOUT, '任务执行超时', $msg);

                    $task = $loadTasks->get($item['id']);
                    // 如果是限制了并发数的任务
                    if ($task && $task['execNum']) {
                        $redisKey = Constants::REDIS_KEY_TASK_EXEC_NUM_PREFIX . $item['taskId'] . ':' . $item['sec'];
                        // 任务执行并发数减一
                        $redisObject->evalScript('decr_exist', $redisKey);
                    }
                }
            }
        }
    }

    /**
     * 获取当前可以执行的任务
     * @return array
     */
    public static function getTasks()
    {
        $data = [];
        if (count(self::$table) > 0) {
            $min = date("YmdHi");
            $time = time();
            foreach (self::$table as $runId => $task) {
                if ($min == $task["minute"]) {
                    if ($time == $task["sec"] && $task["runStatus"] == LoadTasks::RunStatusNormal) {
                        $data[$runId] = [
                            'id'  => $task['id'],
                            'sec' => $task['sec'],
                        ];
                    }
                }
            }
        }
        return $data;
    }
}
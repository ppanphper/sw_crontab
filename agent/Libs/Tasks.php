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
use \Swoole\Process as SwooleProcess;

class Tasks
{
    public static $table;

    /**
     * TYPE_INT 1(Mysql TINYINT): 2 ^ 8 = -128 ~ 127
     * TYPE_INT 2(Mysql SMALLINT): 2 ^ (8 * 2) = -327689 ~ 32767
     * TYPE_INT 4(Mysql INT): 2 ^ (8 * 4) = -2147483648 ~ 2147483647
     * TYPE_INT 8(Mysql BIGINT): 2 ^ (8 * 8) = -9223372036854775808 ~ 9223372036854775807
     * @var array
     */
    private static $column = [
        'minute'    => [SwooleTable::TYPE_INT, 8], // 分钟
        'sec'       => [SwooleTable::TYPE_INT, 8], // 哪一秒执行
        'taskId'    => [SwooleTable::TYPE_INT, 8], // crontab Id
        'runId'     => [SwooleTable::TYPE_INT, 8],
        'runStatus' => [SwooleTable::TYPE_INT, 1],
        'pid'       => [SwooleTable::TYPE_INT, 4], // 进程Id，用于超时强制kill
        'retries'   => [SwooleTable::TYPE_INT, 2], // 重试次数，重试了几次
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
                // 解析crontab规则
                $ret = ParseCrontab::parse($task["rule"], $time);
                if ($ret === false) {
                    log_error(ParseCrontab::$error);
                    continue;
                }
                if(empty($ret)) {
                    continue;
                }

                foreach ($ret as $sec) {
                    // 生成每个任务的运行Id
                    $runId = Donkeyid::getInstance()->dk_get_next_id();
                    // 插入到任务内存表
                    self::$table->set($runId, [
                        'minute' => $minute,
                        'sec' => $time + $sec,
                        'taskId' => $id,
                        'runStatus' => LoadTasks::RUN_STATUS_NORMAL,
                        'retries' => 0,
                    ]);
                }
            }
        }
        return true;
    }

    /**
     * 清理已执行过的任务
     */
    public static function clean()
    {
        if (count(self::$table) > 0) {
            // 超时则把运行中的数量-1
            $loadTasks = LoadTasks::getTable();
            $currentTime = time();
            $timeOutFailedMap = [
                LoadTasks::RUN_STATUS_START => 1,
                LoadTasks::RUN_STATUS_CREATE_PROCESS_SUCCESS => 1,
                LoadTasks::RUN_STATUS_ERROR => 1
            ];
            // 遍历Tasks内存表，不是LoadTasks内存表
            foreach (self::$table as $runId => $task) {
                // 当前时间小于任务执行时间则跳过
                if($currentTime < $task['sec']) {
                    continue;
                }
                // 如果任务已经被删除
                $taskInfo = $loadTasks->get($task['taskId']);
                // key不存在会返回false
                if($taskInfo === false) {
                    self::$table->del($runId);
                    continue;
                }
                $maxTime = intval($taskInfo['maxTime']);
                if ($task['runStatus'] == LoadTasks::RUN_STATUS_SUCCESS || $task['runStatus'] == LoadTasks::RUN_STATUS_FAILED) {
                    self::$table->del($runId);
                    continue;
                }
                // 超时
                if ($maxTime > 0 && ($currentTime - $task['sec']) > $maxTime) {
                    $msg = '最大执行时间: ' . msTimeFormat($maxTime).PHP_EOL;

                    if (isset($timeOutFailedMap[$task['runStatus']])) {
                        if(empty($task['runId'])) {
                            $task['runId'] = $runId;
                        }

                        // 如果是限制了并发数的任务
                        if($taskInfo['execNum']) {
                            $redisKey = Constants::REDIS_KEY_TASK_EXEC_NUM_PREFIX . $task['taskId'] . ':' . $task['sec'];
                            $redisObject = RedisClient::getInstance();
                            // 任务执行并发数减一
                            $redisObject->evalScript('decr_exist', $redisKey);
                        }

                        // 如果超时选项是强杀, 并且进程存在
                        if($taskInfo['timeoutOpt'] == Constants::TIME_OUT_OPT_KILL) {
                            if(Process::getTable()->exist($task['pid']) &&  SwooleProcess::kill($task['pid'], 0)) {
                                $msg .= '强制终止进程';
                                SwooleProcess::kill($task['pid'], SIGKILL);
                            }
                        }
                    }

                    DbLog::log($task['runId'], $task['taskId'], Constants::CUSTOM_CODE_EXEC_TIMEOUT, '任务执行超时', $msg);
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
                if ($min == $task["minute"] && $time == $task["sec"] && $task["runStatus"] == LoadTasks::RUN_STATUS_NORMAL) {
                    $data[$runId] = [
                        'taskId' => $task['taskId'],
                        'sec' => $task['sec'],
                    ];
                }
            }
        }
        return $data;
    }
}
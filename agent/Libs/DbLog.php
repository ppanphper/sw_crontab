<?php
/**
 * Created by PhpStorm.
 * User: pandy
 * Date: 2018/4/25
 * Time: 下午5:05
 */

namespace Libs;

class DbLog
{

    /**
     * @var LogChannel
     */
    protected static $_logChannel = null;

    /**
     * 写入db日志最大重试次数
     *
     * @var int
     */
    protected static $_retryMaxNum = 3;

    public static function init()
    {
        // 初始化日志队列内存表
        self::$_logChannel = new LogChannel();
        // 写入db日志最大重试次数
        self::$_retryMaxNum = config_item('flush_db_log_max_retry_num', 3);

        // 请求完结的时候把日志Flush进文件
        register_shutdown_function([__CLASS__, 'shutdown'], true);
    }

    /**
     * 记录日志到内存队列中，由其他进程刷进数据库
     *
     * @param int $runId
     * @param int $taskId
     * @param int $code 0~255 0=成功 99=解析命令失败 101=变更执行用户失败
     * @param string $title
     * @param string $msg
     * @param float $consumeTime 耗时
     *
     * @return bool
     */
    public static function log($runId, $taskId, $code = 0, $title, $msg = "", $consumeTime = 0.0)
    {
        $log = [
            "task_id"      => $taskId,
            "run_id"       => $runId,
            'code'         => $code,
            "title"        => $title,
            "msg"          => is_scalar($msg) ? $msg : json_encode($msg, JSON_UNESCAPED_UNICODE),
            'consume_time' => $consumeTime,
            "created"      => time(),
        ];
        return self::$_logChannel->push($log);
    }

    /**
     * 变量内的日志记录刷进数据库
     */
    public static function flush()
    {
        $logPrefix = __METHOD__ . ' 日志插入DB失败: ';
        while (true) {
            self::writeLog($logPrefix);
            sleep(1);
        }
    }

    /**
     * @throws \Exception
     */
    public static function shutdown()
    {
        self::writeLog(__METHOD__ . ' 日志插入DB失败: ');
    }

    /**
     * 读取队列把日志写进数据库
     *
     * @param $logPrefix
     *
     * @throws \Exception
     */
    private static function writeLog($logPrefix)
    {
        $db = getDBInstance();
        $stats = self::$_logChannel->stats();
        if ($stats['queue_num'] > 0) {
            for ($i = 0; $i < $stats['queue_num']; $i++) {
                $originLog = self::$_logChannel->pop();
                if ($originLog !== false) {
                    $log = $originLog;
                    unset($log['retry_count']);
                    if (!$db->insertInto('logs', $log)->execute()) {
                        if (!isset($originLog['retry_count'])) {
                            $originLog['retry_count'] = 0;
                        }
                        $originLog['retry_count']++;
                        if ($originLog['retry_count'] <= self::$_retryMaxNum) {
                            // 压入队列等待再次刷
                            self::$_logChannel->push($originLog);
                        } else {
                            log_warning($logPrefix . json_encode($originLog, JSON_UNESCAPED_UNICODE));
                        }
                    }
                }
            }
        }
    }
}
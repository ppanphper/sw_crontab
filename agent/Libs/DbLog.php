<?php
/**
 * Created by PhpStorm.
 * User: pandy
 * Date: 2018/4/25
 * Time: 下午5:05
 */

namespace Libs;

use Models\Logs;
use \Swoole\Table as SwooleTable;

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

    protected static $_table;

    /**
     * TYPE_INT 1(Mysql TINYINT): 2 ^ 8 = -128 ~ 127
     * TYPE_INT 2(Mysql SMALLINT): 2 ^ (8 * 2) = -327689 ~ 32767
     * TYPE_INT 4(Mysql INT): 2 ^ (8 * 4) = -2147483648 ~ 2147483647
     * TYPE_INT 8(Mysql BIGINT): 2 ^ (8 * 8) = -9223372036854775808 ~ 9223372036854775807
     * @var array
     */
    private static $_column = [
        'msg' => [SwooleTable::TYPE_STRING, 65535],
    ];

    /**
     * 创建配置表
     */
    public static function init()
    {
        // 初始化日志队列内存表
        self::$_logChannel = new LogChannel();
        // 写入db日志最大重试次数
        self::$_retryMaxNum = configItem('flush_db_log_max_retry_num', 3);

        /** 用来存储日志内容 */
        self::$_table = new SwooleTable(LOG_TEMP_STORE_MAX_SIZE);
        foreach (self::$_column as $key => $v) {
            self::$_table->column($key, $v[0], $v[1]);
        }
        self::$_table->create();
        /** End */

        // 请求完结的时候把日志Flush进文件
        register_shutdown_function([__CLASS__, 'shutdown']);
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
     * @param array $extendLogField 扩展日志字段
     *
     * @return bool
     */
    public static function log($runId, $taskId, $code = 0, $title, $msg = '', $consumeTime = 0.0, array $extendLogField = [])
    {
        // 当前第几次重试
        $retries = Tasks::$table->get($runId, 'retries');
        $log = [
            'taskId'      => $taskId,
            'runId'       => $runId,
            'code'        => $code,
            'title'       => $title,
            'msg'         => is_scalar($msg) ? $msg : json_encode($msg, JSON_UNESCAPED_UNICODE),
            'consumeTime' => $consumeTime,
            'created'     => microtime(true),
            'retries'     => $retries,
        ];
        if ($extendLogField) {
            $log = array_merge($log, $extendLogField);
        }
        return self::$_logChannel->push($log);
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
    public static function endLog($runId, $taskId, $code = 0, $title, $msg = '', $consumeTime = 0.0)
    {
        return self::log($runId, $taskId, $code, $title, $msg, $consumeTime, [
            'end' => 1,
        ]);
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

    public static function shutdown()
    {
        self::writeLog(__METHOD__ . ' 日志插入DB失败: ');
    }

    /**
     * 读取队列把日志写进数据库
     * TODO 批量插入
     *
     * @param $logPrefix
     */
    private static function writeLog($logPrefix)
    {
        try {
            $stats = self::$_logChannel->stats();
            if ($stats['queue_num'] > 0) {
                for ($i = 0; $i < $stats['queue_num']; $i++) {
                    $originLog = self::$_logChannel->pop();
                    if ($originLog !== false) {
                        // key = 任务Id + 运行Id + 第几次重试
                        $key = self::getMemoryTableKey($originLog['taskId'], $originLog['runId'], $originLog['retries']);
                        $msg = self::$_table->get($key, 'msg');
                        if ($msg === false) {
                            $msg = '';
                        }
                        // 不是日志入库重试
                        if (!isset($originLog['retryCount'])) {
                            $msg .= '[' . Log::uDate(null, $originLog['created']) . ' ' . $originLog['title'] . ']' . PHP_EOL;
                            if ($originLog['msg']) {
                                $msg .= $originLog['msg'] . PHP_EOL;
                            }
                            // 暂存日志内容
                            self::$_table->set($key, ['msg' => $msg]);
                        }
                        // 如果不是最后一条日志，就跳过
                        if (!isset($originLog['end']) || !$originLog['end']) {
                            continue;
                        }
                        // 删除并发日志
                        if ($originLog['code'] == Constants::CUSTOM_CODE_CONCURRENCY_LIMIT) {
                            self::$_table->del($key);
                            continue;
                        }
                        // 存储日志
                        if (!Logs::saveLog([
                            'taskId'        => $originLog['taskId'],
                            'runId'         => $originLog['runId'],
                            'code'          => $originLog['code'],
                            'title'         => $originLog['title'],
                            'msg'           => $msg,
                            'consumeTime'   => $originLog['consumeTime'],
                            'created'       => $originLog['created'],
                        ])) {
                            if (!isset($originLog['retryCount'])) {
                                $originLog['retryCount'] = 0;
                            }
                            $originLog['retryCount']++;
                            if ($originLog['retryCount'] <= self::$_retryMaxNum) {
                                // 压入队列等待再次刷
                                self::$_logChannel->push($originLog);
                            } else {
                                // 入库失败重试次数达到阀值就删除掉
                                self::$_table->del($key);
                                logWarning($logPrefix . json_encode($originLog, JSON_UNESCAPED_UNICODE));
                            }
                        } else {
                            // 入库后就删除掉
                            self::$_table->del($key);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            logError($logPrefix . $e->getMessage());
        }
    }

    /**
     * 获取内存表的Key
     *
     * @param $taskId
     * @param $runId
     * @param $retries
     *
     * @return string
     */
    public static function getMemoryTableKey($taskId, $runId, $retries)
    {
        return $taskId . $runId . $retries;
    }

    public static function getTable() {
        return self::$_table;
    }
}
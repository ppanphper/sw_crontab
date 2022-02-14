<?php
/**
 * worker服务中  新创建一个进程去执行命令
 * Created by PhpStorm.
 * User: liuzhiming
 * Date: 16-8-22
 * Time: 下午6:04
 */

namespace Libs;

use \Swoole\Table as SwooleTable;
use \Swoole\Process as SwooleProcess;

class Process
{
    private static $_table;
    private static $_column = [
        'taskId' => [SwooleTable::TYPE_INT, 8],
        'runId'  => [SwooleTable::TYPE_INT, 8],
        'sec'    => [SwooleTable::TYPE_INT, 8],
        'start'  => [SwooleTable::TYPE_FLOAT, 8],
        'end'    => [SwooleTable::TYPE_FLOAT, 8],
        'pipe'   => [SwooleTable::TYPE_INT, 8],
    ];
    const PROCESS_START = 0;//程序开始运行
    const PROCESS_STOP = 1;//程序结束运行

    private static $_processList = [];
    private static $_processStdOut = [];
    private static $_maxStdOut = 60000;
    // 输出写入按运行Id生成的日志文件
//    private static $_processLogWriteFile = [];

    public $task;

    public static function init()
    {
        self::$_table = new SwooleTable(PROCESS_MAX_SIZE);
        foreach (self::$_column as $key => $v) {
            self::$_table->column($key, $v[0], $v[1]);
        }
        self::$_table->create();
    }

    /**
     * 注册信号监听子进程，当子进程运行完成就写日志记录
     *
     * 在Worker进程运行
     *
     * @param object $server SwooleServer
     */
    public static function signal($server)
    {
        SwooleProcess::signal(SIGCHLD, function ($sig) use ($server) {
            //必须为false，非阻塞模式
            while ($ret = SwooleProcess::wait(false)) {
                $pid = $ret['pid'];
                if ($processTask = self::$_table->get($pid)) {
                    $processTask['end'] = microtime(true);
                    $processTask['stdout'] = isset(self::$_processStdOut[$pid]) ? self::$_processStdOut[$pid] : "";

                    $metric = Constants::MONITOR_KEY_EXEC_SUCCESS;
                    $code = Constants::CUSTOM_CODE_END_RUN;
                    $runStatus = LoadTasks::RUN_STATUS_SUCCESS;
                    if ($ret['code'] != $code) {
                        if ($ret['code'] == Constants::EXIT_CODE_CONCURRENT) {
                            $code = Constants::CUSTOM_CODE_CONCURRENCY_LIMIT;
                            $metric = Constants::MONITOR_KEY_CONCURRENCY_LIMIT;
                        } // 不是正常退出
                        else {
                            $code = $ret['code'];
                            // 解析cmd命令失败
                            if ($code == Constants::EXIT_CODE_CMD_PARSE_FAILED) {
                                $code = Constants::CUSTOM_CODE_CMD_PARSE_FAILED;
                            }
                            // 变更运行时用户失败
                            if ($code == Constants::EXIT_CODE_RUN_USER_CHANGE_FAILED) {
                                $code = Constants::CUSTOM_CODE_RUN_USER_CHANGE_FAILED;
                            }
                            $metric = Constants::MONITOR_KEY_EXEC_FAILED;
                            $runStatus = LoadTasks::RUN_STATUS_FAILED;
                        }
                    }
                    // 关闭管道监听
                    swoole_event_del($processTask['pipe']);
                    self::$_table->del($pid);

                    $consumeTime = $processTask['end'] - $processTask['start'];

                    if ($ret['signal']) {
                        $processTask['stdout'] = '进程被强制终止, 信号: ' . $ret['signal'] . PHP_EOL . $processTask['stdout'];
                    }
                    DbLog::endLog($processTask['runId'], $processTask['taskId'], $code, "任务运行完成", $processTask['stdout'], $consumeTime);

                    // 执行失败报警 需要放到最后面，以免查不到日志
                    if ($code != Constants::CUSTOM_CODE_END_RUN && $code != Constants::CUSTOM_CODE_CONCURRENCY_LIMIT) {
                        Report::taskExecFailed($processTask['taskId'], $processTask['runId'], $code, $ret['signal'], $processTask['stdout']);
                    }

                    /**
                     * 如果不是正常结束运行，就要看任务是否需要重试
                     * 过滤掉命令解析错误，变更用户错误的情况
                     */
                    if (!in_array($code, [
                        Constants::CUSTOM_CODE_END_RUN,
                        Constants::CUSTOM_CODE_CMD_PARSE_FAILED,
                        Constants::CUSTOM_CODE_RUN_USER_CHANGE_FAILED
                    ])) {
                        $loadTasksTable = LoadTasks::getTable();
                        $taskInfo = $loadTasksTable->get($processTask['taskId']);
                        // 如果任务需要重试
                        if (!empty($taskInfo['retries']) && Tasks::$table->exist($processTask['runId'])) {
                            // 下一次重试的数值是否超过阀值
                            $nextRetries = Tasks::$table->incr($processTask['runId'], 'retries');
                            if ($nextRetries !== false) {
                                if ($nextRetries <= $taskInfo['retries']) {
                                    // 标识运行状态为重试
                                    $runStatus = LoadTasks::RUN_STATUS_RETIRES;
                                    // 指定重试执行时间
                                    $sec = time() + $taskInfo['retryInterval'];
                                    $data = [
                                        $processTask['runId'] => [
                                            'taskId'         => $processTask['taskId'],
                                            'sec'            => $sec,
                                            'currentRetries' => $nextRetries, // 下一次重试的数值
                                        ]
                                    ];
                                    do {
                                        // 随机选择一个worker进程进行重试，can't send messages to self
                                        $dstWorkerId = mt_rand(Server::WORKER_EXEC_TASKS, $server->setting['worker_num'] - 1);
                                    } while ($dstWorkerId == $server->worker_id);
                                    if ($taskInfo['retryInterval']) {
                                        // 定时间隔时间执行
                                        $server->after($taskInfo['retryInterval'] * 1000, function () use ($server, $dstWorkerId, $data) {
                                            // 发送任务执行重试
                                            $server->sendMessage($data, $dstWorkerId);
                                        });
                                    } else {
                                        // 立即发送任务执行重试
                                        $server->sendMessage($data, $dstWorkerId);
                                    }
                                }
                            }
                        }
                    }
                    /** End */
                    // 任务存在，变更状态为已完成、解析失败、变更用户失败、重试
                    if (Tasks::$table->exist($processTask['runId'])) {
                        $task = [
                            'runStatus' => $runStatus
                        ];
                        // 设置执行时间，以免被判定为超时
                        if (!empty($sec)) {
                            $task['sec'] = $sec;
                        }
                        Tasks::$table->set($processTask['runId'], $task);
                    }

                    // 上报监控系统
                    Report::monitor($metric . '.' . $processTask['taskId']);
                }
                // 关闭创建的好的管道
                self::$_processList[$pid]->close();
//                unset(self::$_processList[$pid], self::$_processStdOut[$pid], self::$_processLogWriteFile[$pid]);
                unset(self::$_processList[$pid], self::$_processStdOut[$pid]);
            }

        });
    }

    /**
     * 创建一个子进程执行任务
     *
     * @param array $task
     *
     * $task = [
     *  'taskId'     => $taskId,
     *  'command'    => $task['command'],
     *  'agents'     => $task['agents'],
     *  'name'       => $task['name'],
     *  'execNum'    => $task['execNum'],
     *  'runUser'    => $task['runUser'],
     *  'sec'        => $task['sec'],
     *  'runId'      => $runId,
     *  'retries'    => $item['currentRetries'], 当前第几次重试
     * ]
     *
     * @return bool
     */
    public static function create_process($task)
    {
        $self = new self();
        $self->task = $task;
        $process = new SwooleProcess([$self, 'exec'], true, true);
        swoole_event_add($process->pipe, function ($pipe) use ($process) {
            $pid = $process->pid;
            !isset(self::$_processStdOut[$pid]) && self::$_processStdOut[$pid] = "";
            // 默认每次读取8192字节
            $tmp = self::$_processList[$pid]->read();
            if ($tmp) {
                $len = mb_strlen(self::$_processStdOut[$pid]);
                // 如果一次性读取超过了最大长度，就截取
                if (($length = (self::$_maxStdOut - $len)) > 0) {
                    self::$_processStdOut[$pid] .= mb_substr($tmp, 0, $length);
                }
            }
        });

        $pid = $process->start();
        if ($pid === false) {
            // 标记任务状态为创建进程失败
            if (Tasks::$table->exist($task['runId'])) {
                Tasks::$table->set($task['runId'], ['runStatus' => LoadTasks::RUN_STATUS_CREATE_PROCESS_FAILED]);
            }

            // 上报监控系统创建进程失败
            Report::monitor(Constants::MONITOR_KEY_CREATE_PROCESS_FAILED . '.' . $task['taskId']);
            logError(__METHOD__ . ' 创建进程失败 errorMsg = ' . swoole_strerror(swoole_errno()));
            return false;
        }

        self::$_table->set($pid, [
            'taskId' => $task['taskId'],
            'runId'  => $task['runId'],
            'sec'    => $task['sec'],
            'start'  => microtime(true),
            'pipe'   => $process->pipe,
        ]);

        // 是否记录输出到日志文件
        /*if ($task['logOpt'] == Constants::LOG_OPT_WRITE_FILE) {
            self::$_processLogWriteFile[$pid] = [
                'taskId' => $task['taskId'],
                'runId'  => $task['runId'],
                'sec'    => $task['sec'],
            ];
        }*/

        self::$_processList[$pid] = $process;

        // 上报监控系统创建进程成功
        Report::monitor(Constants::MONITOR_KEY_CREATE_PROCESS_SUCCESS . '.' . $task['taskId']);

        // 标记任务状态为创建进程成功
        if (Tasks::$table->exist($task['runId'])) {
            Tasks::$table->set($task['runId'], [
                'runStatus' => LoadTasks::RUN_STATUS_CREATE_PROCESS_SUCCESS,
                'pid'       => $pid,
            ]);
        }
        return true;
    }

    /**
     * 子进程执行的入口
     *
     * 此处是一个单独的子进程，不是worker进程中
     * 子进程会继承父进程的内存和文件句柄
     * 子进程在启动时会清除从父进程继承的EventLoop、Signal、Timer
     *
     * 子进程启动后会自动清除父进程中swoole_timer_tick创建的定时器、swoole_process::signal监听的信号和swoole_event_add添加的事件监听
     * 子进程会继承父进程创建的$redis连接对象，父子进程使用的连接是同一个
     *
     * @param SwooleProcess $process
     *
     * @throws \Exception
     */
    public function exec(SwooleProcess $process)
    {
        if (self::$_processList) {
            foreach (self::$_processList as $p) {
                $p->close();
            }
        }
        self::$_processList = [];
        $command = $this->task['command'];
        if ($this->task['runUser'] && !self::changeUser($this->task['runUser'])) {
            $msg = 'RunUser: ' . $this->task['runUser'];
            // 上报监控系统变更运行时用户失败
            Report::monitor(Constants::MONITOR_KEY_RUN_USER_CHANGE_FAILED . '.' . $this->task['taskId']);
            DbLog::log($this->task['runId'], $this->task['taskId'], Constants::CUSTOM_CODE_RUN_USER_CHANGE_FAILED, '子进程修改运行时用户失败', $msg);
            $process->exit(Constants::EXIT_CODE_RUN_USER_CHANGE_FAILED);
        }

        // 如果设置了并发数限制, 并且不是重试(重试不占并发数)
        if ($this->task['execNum'] && empty($this->task['retries'])) {
            // 加载Redis配置文件
            $redisConfig = configItem(null, null, 'redis');
            // TODO 这里有个坑，短连接建立可能会超过设定的定时执行时间
            $redisObject = new RedisClient($redisConfig);
            // 如果是限制并发任务，开始申请执行权限
            $redisKey = Constants::REDIS_KEY_TASK_EXEC_NUM_PREFIX . $this->task['taskId'] . ':' . $this->task['sec'];
            /**
             * 获取这个任务的这一秒有多少并发数
             * 如果没有超限，就把计数器+1并返回累加后的值[1, 累加后的值]
             * 如果超限，就把当前的值返回[0, 当前的值]
             */
            $result = $redisObject->evalScript('incr_max', $redisKey, [$this->task['execNum'], 60]);
            //限制任务多次执行，保证同时只有符合数量的任务运行。如果限制条件为0，则不限制数量
            if ($result && $result[0] == 0) {
                // 不记录并发超限日志，最终都要删除的
//                $msg = '当前并发数: ' . $result[1] . PHP_EOL
//                    . '限制并发数: ' . $this->task['execNum'];
//                DbLog::log($this->task['runId'], $this->task['taskId'], Constants::CUSTOM_CODE_CONCURRENCY_LIMIT, '并发达到阀值，本次不执行', $msg);
                $process->exit(Constants::EXIT_CODE_CONCURRENT);
            }
        }

        // 上报监控系统任务准备开始运行
        Report::monitor(Constants::MONITOR_KEY_CHILD_PROCESS_STARTS_RUN . '.' . $this->task['taskId']);
        DbLog::log($this->task['runId'], $this->task['taskId'], Constants::CUSTOM_CODE_CHILD_PROCESS_STARTS_RUN, '任务开始执行', $this->task['command']);

        logInfo('任务开始执行' . (!empty($this->task['retries']) ? '(第' . $this->task['retries'] . '次重试)' : '') . ': Id = ' . $this->task['taskId'] . '; runId = ' . $this->task['runId'] . '; command = ' . $this->task['command']);
        if (isWindowsOS()) {
            $bool = $process->exec('cmd', ['/C', $command]);
        } else {
            $bool = $process->exec('/bin/sh', ['-c', $command]);
        }
        // 执行失败
        if (!$bool) {
            $process->exit(Constants::CUSTOM_CODE_EXEC_FAILED);
        }
    }

    /**
     * 修改运行时用户
     *
     * @param $user
     *
     * @return bool
     */
    public static function changeUser($user)
    {
        if (!function_exists('posix_getpwnam')) {
            logError(__METHOD__ . ": require posix extension.");
            return false;
        }
        $user = posix_getpwnam($user);
        if ($user) {
            posix_setuid($user['uid']);
            posix_setgid($user['gid']);
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return SwooleTable|null
     */
    public static function getTable()
    {
        return self::$_table;
    }
}
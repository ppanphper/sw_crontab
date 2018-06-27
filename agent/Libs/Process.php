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
    private static $table;
    static private $column = [
        'taskId' => [SwooleTable::TYPE_INT, 8],
        'runId'  => [SwooleTable::TYPE_INT, 8],
        'sec'    => [SwooleTable::TYPE_INT, 8],
        'start'  => [SwooleTable::TYPE_FLOAT, 8],
        'end'    => [SwooleTable::TYPE_FLOAT, 8],
        'pipe'   => [SwooleTable::TYPE_INT, 8],
    ];
    const PROCESS_START = 0;//程序开始运行
    const PROCESS_STOP = 1;//程序结束运行

    public $task;
    static public $process_list = [];
    private static $process_stdout = [];
    private static $max_stdout = 10240;

    public static function init()
    {
        self::$table = new SwooleTable(PROCESS_MAX_SIZE);
        foreach (self::$column as $key => $v) {
            self::$table->column($key, $v[0], $v[1]);
        }
        self::$table->create();
    }

    /**
     * 注册信号监听子进程，当子进程运行完成就写日志记录
     *
     * 在Worker进程运行
     */
    public static function signal()
    {
        SwooleProcess::signal(SIGCHLD, function ($sig) {
//			$redisObject = RedisClient::getInstance();
//			$loadTasksTable = LoadTasks::getTable();
            //必须为false，非阻塞模式
            while ($ret = SwooleProcess::wait(false)) {
                $pid = $ret['pid'];
                if ($task = self::$table->get($pid)) {
                    $task['end'] = microtime(true);
                    $task['stdout'] = isset(self::$process_stdout[$pid]) ? self::$process_stdout[$pid] : "";

                    $metric = Constants::MONITOR_KEY_EXEC_SUCCESS;
                    $code = Constants::CUSTOM_CODE_END_RUN;
                    $runStatus = LoadTasks::RunStatusSuccess;
                    if ($ret['code'] != 0) {
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
                            $runStatus = LoadTasks::RunStatusFailed;
                            Report::taskExecFailed($task['taskId'], $task['runId'], $code, $ret['signal']);
                        }
                    }
                    // 上报监控系统
                    Report::monitor($metric . '.' . $task['taskId']);
                    // 如果执行结束后，把并发数减去，会导致其他机器也可以执行(如果执行的太快，会出现并发限制无效)
//                    $execNum = $loadTasksTable->get($task['taskId'], 'execNum');
//                    if ($execNum) {
//                        $redisKey = Constants::REDIS_KEY_TASK_EXEC_NUM_PREFIX . $task['taskId'] . ':' . $task['sec'];
//                        // 任务执行并发数减一
//                        $redisObject->evalScript('decr_exist', $redisKey);
//                    }
                    // 任务存在，变更状态为已完成
                    if (Tasks::$table->exist($task['runId'])) {
                        Tasks::$table->set($task['runId'], ['runStatus' => $runStatus]);
                    }
                    // 关闭管道监听
                    swoole_event_del($task['pipe']);
                    self::$table->del($pid);
                    $consumeTime = $task['end'] - $task['start'];
                    DbLog::log($task['runId'], $task['taskId'], $code, "进程运行完成", $task['stdout'], $consumeTime);
                }
                // 关闭创建的好的管道
                self::$process_list[$pid]->close();
                unset(self::$process_list[$pid], self::$process_stdout[$pid]);
            }

        });
    }

    /**
     * 创建一个子进程执行任务
     *
     * @param array $task
     *
     * $task = [
     *  'id'         => $taskId,
     *  'command'    => $task['command'],
     *  'agents'     => $task['agents'],
     *  'name'       => $task['name'],
     *  'execNum'    => $task['execNum'],
     *  'runUser'    => $task['runUser'],
     *  'sec'        => $task['sec'],
     *  'runId'      => $runId,
     * ]
     *
     * @return bool
     */
    public static function create_process($task)
    {
        $self = new self();
        $self->task = $task;
        $process = new SwooleProcess([$self, 'exec'], true, true);
        $pid = $process->start();
        if ($pid === false) {
            // 标记任务状态为创建进程失败
            if (Tasks::$table->exist($task['runId'])) Tasks::$table->set($task['runId'], ["runStatus" => LoadTasks::RunStatusCreateProcessFailed]);
            // 上报监控系统创建进程失败
            Report::monitor(Constants::MONITOR_KEY_CREATE_PROCESS_FAILED . '.' . $task['id']);
            log_error(__METHOD__ . ' 创建进程失败 errorMsg = ' . swoole_strerror(swoole_errno()));
            return false;
        }
        swoole_event_add($process->pipe, function ($pipe) use ($pid) {
            !isset(self::$process_stdout[$pid]) && self::$process_stdout[$pid] = "";
            $tmp = self::$process_list[$pid]->read();
            $len = strlen(self::$process_stdout[$pid]);
            if ($len + strlen($tmp) <= self::$max_stdout) {
                self::$process_stdout[$pid] .= $tmp;
            }
        });
        self::$table->set($pid, [
            'taskId' => $task['id'],
            'runId'  => $task['runId'],
            'sec'    => $task['sec'],
            'start'  => microtime(true),
            'pipe'   => $process->pipe
        ]);
        self::$process_list[$pid] = $process;
        // 上报监控系统创建进程成功
        Report::monitor(Constants::MONITOR_KEY_CREATE_PROCESS_SUCCESS . '.' . $task['id']);
        // 标记任务状态为创建进程成功
        if (Tasks::$table->exist($task['runId'])) Tasks::$table->set($task['runId'], ["runStatus" => LoadTasks::RunStatusCreateProcessSuccess]);
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
        foreach (self::$process_list as $p) {
            $p->close();
        }
        self::$process_list = [];
        $command = $this->task['command'];
        $pattern = Constants::CMD_PARSE_PATTERN;
        preg_match_all($pattern, $command, $matches);
        if (empty($matches[1]) || empty($matches[1][0])) {
            $msg = '解析结果: ' . var_export($matches, true);
            // 上报监控系统解析命令失败
            Report::monitor(Constants::MONITOR_KEY_CMD_PARSE_FAILED . '.' . $this->task['id']);
            DbLog::log($this->task['runId'], $this->task['id'], Constants::CUSTOM_CODE_CMD_PARSE_FAILED, '命令解析失败', $msg);
            exit(Constants::EXIT_CODE_CMD_PARSE_FAILED);
        }
        $execFile = $matches[1][0];
        $args = [];
        if (count($matches[1]) > 1) {
            $args = array_slice($matches[1], 1);
            foreach ($args as &$val) {
                // 去除双引号、单引号
                $val = trim($val, '"\'');
            }
        }
        if ($this->task['runUser'] && !self::changeUser($this->task['runUser'])) {
            $msg = 'RunUser: ' . $this->task['runUser'];
            // 上报监控系统变更运行时用户失败
            Report::monitor(Constants::MONITOR_KEY_RUN_USER_CHANGE_FAILED . '.' . $this->task['id']);
            DbLog::log($this->task['runId'], $this->task['id'], Constants::CUSTOM_CODE_RUN_USER_CHANGE_FAILED, '子进程修改运行时用户失败', $msg);
            exit(Constants::EXIT_CODE_RUN_USER_CHANGE_FAILED);
        }

        // 如果设置了并发数限制
        if ($this->task['execNum']) {
            // 加载Redis配置文件
            $redisConfig = config_item(null, null, 'redis');
            $redisObject = new RedisClient($redisConfig);
            // 如果是限制并发任务，开始申请执行权限
            $redisKey = Constants::REDIS_KEY_TASK_EXEC_NUM_PREFIX . $this->task['id'] . ':' . $this->task['sec'];
            /**
             * 获取这个任务的这一秒有多少并发数
             * 如果没有超限，就把计数器+1并返回累加后的值[1, 累加后的值]
             * 如果超限，就把当前的值返回[0, 当前的值]
             */
            $result = $redisObject->evalScript('incr_max', $redisKey, [$this->task['execNum'], 60]);
            //限制任务多次执行，保证同时只有符合数量的任务运行。如果限制条件为0，则不限制数量
            if ($result && $result[0] == 0) {
                $msg = '并发达到阀值，本次不执行' . PHP_EOL
                    . '当前并发数: ' . $result[1] . PHP_EOL
                    . '限制并发数: ' . $this->task['execNum'];
                echo $msg;
                exit(Constants::EXIT_CODE_CONCURRENT);
            }
        }

        // 上报监控系统创建进程失败
        Report::monitor(Constants::MONITOR_KEY_CHILD_PROCESS_STARTS_RUN . '.' . $this->task['id']);
        DbLog::log($this->task['runId'], $this->task['id'], Constants::CUSTOM_CODE_CHILD_PROCESS_STARTS_RUN, '子进程任务开始执行');
        $bool = $process->exec($execFile, $args);
        // 执行失败
        if (!$bool) {
            exit(Constants::CUSTOM_CODE_EXEC_FAILED);
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
            log_error(__METHOD__ . ": require posix extension.");
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
}
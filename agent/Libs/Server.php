<?php

namespace Libs;

use \Swoole\Server as SwooleServer;
use \Swoole\Process as SwooleProcess;

/**
 * Class Server
 * @package Swoole\Network
 */
class Server extends ServerBase
{
    const WORKER_NUM = 10;
    const TASK_WORKER_NUM = 4;

    /**
     * $worker_id是一个从0 - $worker_num - 1之间的数字，表示这个Worker进程的ID
     * 所以Task进程的Id是taskWorkerId - $worker_num 到 $task_worker_num - 1之间的数字，表示这个Task Worker进程的ID
     */
    const TASK_LOAD_TASKS = 0; // 从数据库中载入所有任务，监听是否有变更
    const TASK_PARSE_TASKS = 1;//分析所有任务，把这一分钟的任务载入任务进程
    const TASK_GET_TASKS = 2; //每一秒获取当前可以执行的任务
    const TASK_MANAGER_TASKS = 3;//管理task状态

    const WORKER_EXEC_TASKS = 0;//创建进程执行任务

    /**
     * Worker Task Start
     * @var array
     */
    public $_initTaskMaps = [];

    protected $pid_file;
    // 状态进程Pid文件
    public static $statsPidFile;

    public function __construct($host, $port = 0, $ssl = false)
    {
        parent::__construct($host, $port, $ssl);

        $this->_config['worker_num'] = self::WORKER_NUM;
        $this->_config['task_worker_num'] = self::TASK_WORKER_NUM;

        $this->_initTaskMaps = [
            // 加载任务到内存表，相当于crontab表
            self::TASK_LOAD_TASKS    => function ($server) {
                $this->setProcessName("Task|LoadTask");
                loadTasks::load();
                // 加载完任务，就产生下一分钟需要执行的任务
                Tasks::generateTask();
                $server->tick(config_item('monitor_reload_interval', 10) * 1000, function () use ($server) {
                    loadTasks::monitorReload();
                });
            },
            // 产生下一分钟的任务载入到任务内存表，与上面的内存表不是同一个
            self::TASK_PARSE_TASKS   => function ($server) {
                $this->setProcessName("Task|ParseTask");
                //准点载入下一分钟任务到任务内存表中
                $server->after((60 - date("s")) * 1000, function () use ($server) {
                    Tasks::generateTask();
                    $server->tick(60000, function () use ($server) {
                        Tasks::generateTask();
                    });
                });
            },
            // 获取这一秒要执行的任务，转发给执行任务的worker进程(task进程不支持asyncIo, 无法监听管道是否可读)
            self::TASK_GET_TASKS     => function ($server) {
                $this->setProcessName("Task|SendWorker");
                $dstWorkerId = self::WORKER_EXEC_TASKS;
                $server->tick(1000, function () use ($server, &$dstWorkerId) {
                    $tasks = Tasks::getTasks();
                    if ($tasks) {
                        // 轮询发送
                        $server->sendMessage($tasks, $dstWorkerId);
                        $dstWorkerId++;
                        if ($dstWorkerId >= $server->setting['worker_num']) {
                            $dstWorkerId = self::WORKER_EXEC_TASKS;
                        }
                    }
                });
            },
            // 判断任务是否正常执行
            self::TASK_MANAGER_TASKS => function ($server) {
                $this->setProcessName("Task|ManageTask");
            }
        ];
    }

    public static function init()
    {
        parent::init();
        self::$_startMethodMaps = array_merge(self::$_startMethodMaps, [
            'stats' => function ($serverPID, $opt) {
                if (empty($serverPID) || !is_file(self::$statsPidFile)) {
                    exit("Server is not running\n");
                }
                $pid = file_get_contents(self::$statsPidFile);
                posix_kill($pid, SIGUSR1);
                exit(0);
            }
        ]);
    }

    /**
     * 服务启动
     *
     * @param array $setting
     */
    public function run(array $setting = [])
    {
        //merge config
        if (!empty($setting)) {
            $this->_config = array_merge($this->_config, $setting);
        }

        if (self::$pidFile) {
            $this->_config['pid_file'] = self::$pidFile;
        }
        if (!empty(self::$options['daemon'])) {
            $this->_config['daemonize'] = true;
        }

        if (!empty(self::$options['thread'])) {
            $this->_config['reator_num'] = intval(self::$options['thread']);
        }

        $this->sw->on('Start', [$this, 'onMasterStart']);
        $this->sw->on('ManagerStart', [$this, 'onManagerStart']);
        $this->sw->on('Shutdown', [$this, 'onShutdown']);
        $this->sw->on('ManagerStop', [$this, 'onManagerStop']);
        $this->sw->on('WorkerStart', [$this, 'onWorkerStart']);
        $this->sw->on('Connect', [$this, 'onConnect']);
        $this->sw->on('Receive', [$this, 'onReceive']);
        $this->sw->on('PipeMessage', [$this, 'onPipeMessage']);
        $this->sw->on('Close', [$this, 'onClose']);
        $this->sw->on('WorkerStop', [$this, 'onWorkerStop']);

        if (is_callable([$this, 'onTask'])) {
            $this->sw->on('Task', [$this, 'onTask']);
            $this->sw->on('Finish', [$this, 'onFinish']);
        }

        parent::run();
    }

    /**
     * 服务启动时做一些初始化操作
     *
     * @return mixed
     */
    protected function initServer()
    {
        Report::init();
        $monitorAlarmProcessNum = config_item('monitor_alarm_process_num', 1);
        for ($i = 0; $i < $monitorAlarmProcessNum; $i++) {
            $this->sw->addProcess(new SwooleProcess(function ($process) use ($i) {
                $process->name($this->_serverName . '|monitorAlarm|' . $i);
                Report::monitorAlarm();
            }));
        }
        /** End */

        /**
         * DB日志进程，异步刷日志到数据库
         */
        DbLog::init();
        $flushDbLogProcessNum = config_item('flush_db_log_process_num', 2);
        for ($i = 0; $i < $flushDbLogProcessNum; $i++) {
            $this->sw->addProcess(new SwooleProcess(function ($process) use ($i) {
                $process->name($this->_serverName . '|logFlushToDB|' . $i);
                DbLog::flush();
            }));
        }
        /** End */

        if (self::$statsPidFile) {
            $this->sw->addProcess(new SwooleProcess(function ($process) {
                $process->name($this->_serverName . '|listenPipeProcess');
                file_put_contents(self::$statsPidFile, $process->pid);
                SwooleProcess::signal(SIGUSR1, function ($sig) {
                    $tasks = LoadTasks::getTable();
                    $content = '';
                    foreach ($tasks as $id => $task) {
                        $content .= 'id = ' . $id . '; task = ' . var_export($task, true) . PHP_EOL;
                    }
                    self::formatOutput($content);
                });
            }));
        }

        parent::initServer();

        // 连接中心服注册服务
        $this->sw->addProcess(new SwooleProcess([$this, 'register']));
    }


    /**
     * 主进程启动的时候触发
     *
     * @param SwooleServer $server
     */
    public function onMasterStart(SwooleServer $server)
    {
        $this->setProcessName(': master -host=' . $this->host . ' -port=' . $this->port, '');
        $this->formatOutput("Master PID = {$server->master_pid}");
        $this->formatOutput("Manager PID = {$server->manager_pid}");
        $this->formatOutput("Swoole Version = [" . SWOOLE_VERSION . "]");
        $this->formatOutput("Server IP = " . SERVER_INTERNAL_IP);
        $this->formatOutput("Listen IP = {$this->host}");
        $this->formatOutput("Listen Port = {$this->port}");
        $this->formatOutput("Worker Number = {$server->setting['worker_num']}");
        $this->formatOutput("Task Number = {$server->setting['task_worker_num']}");
    }

    /**
     * Worker 和 Task进程启动的时候触发
     *
     * @param SwooleServer $server
     * @param $worker_id
     */
    public function onWorkerStart(SwooleServer $server, $worker_id)
    {
        if ($server->taskworker) {
            $this->initTask($server, $worker_id);
            $taskId = $worker_id - $server->setting['worker_num'];
            // 获取当前进程Id
            if (isset($this->_initTaskMaps[$taskId])) {
                // 执行对应的匿名函数
                $this->_initTaskMaps[$taskId]($server, $taskId);
            } else {
                $this->setProcessName("Task|{$worker_id}");
            }
        } else {
            $this->initWork($server, $worker_id);
            //worker
            $this->setProcessName("Worker|ExecTask|{$worker_id}");
            Process::signal();//注册信号
        }
    }

    /**
     * Worker 和 Task 进程间通信接收数据
     *
     * @param SwooleServer $server
     * @param $src_worker_id
     * @param $data
     */
    public function onPipeMessage(SwooleServer $server, $src_worker_id, $data)
    {
        $workerId = $server->worker_id;
        // 如果是worker进程接收到，准备创建进程执行
        if ($workerId < $server->setting['worker_num']) {
            $loadTasksTable = LoadTasks::getTable();
            $ret = [];
            foreach ($data as $runId => $item) {
                $taskId = $item['id'];
                // 当前执行任务的时间戳
                $sec = $item['sec'];
                $task = $loadTasksTable->get($taskId);
                // 这个任务存在，如果被删除了，就不再执行
                if ($task) {
                    $msg = '任务名称: ' . $task['name'] . PHP_EOL
                        . '指定执行时间: ' . date($this->dateFormat, $sec) . PHP_EOL
                        . '执行节点: ' . SERVER_INTERNAL_IP;
                    $tmp = [
                        'id'      => $taskId,
                        'command' => $task['command'],
                        'agents'  => $task['agents'],
                        'name'    => $task['name'],
                        'execNum' => $task['execNum'],
                        'runUser' => $task['runUser'],
                        'sec'     => $sec,
                        'runId'   => $runId,
                    ];
                    //正在运行标示
                    if (Tasks::$table->exist($runId)) Tasks::$table->set($runId, ["runStatus" => LoadTasks::RunStatusStart, "runId" => $runId]);

                    DbLog::log($tmp["runId"], $taskId, Constants::CUSTOM_CODE_READY_START, "任务准备开始", $msg);
                    $ret[$runId] = [
                        "id"  => $taskId,
                        "ret" => Process::create_process($tmp)
                    ];
                }
            }
            $server->sendMessage($ret, $server->setting['worker_num'] + self::TASK_MANAGER_TASKS);
        } else if ($workerId == ($server->setting['worker_num'] + self::TASK_MANAGER_TASKS)) {
            foreach ($data as $runId => $v) {
                $taskId = $v['id'];
                $title = '创建进程失败';
                $code = Constants::CUSTOM_CODE_CREATE_CHILD_PROCESS_FAILED;
                if ($v["ret"]) {
                    $title = '创建进程成功';
                    $code = Constants::CUSTOM_CODE_CREATE_CHILD_PROCESS_SUCCESS;
                } else {
                    Report::taskCreateProcessFailed($taskId, $runId);//报警
                }
                DbLog::log($runId, $taskId, $code, $title);
            }
        }
    }

    public static function setStatsPidFile($statsPidFile)
    {
        self::$statsPidFile = $statsPidFile;
    }

    /**
     * 接收数据进行处理
     *
     * @param SwooleServer $server
     * @param $fd
     * @param $from_id
     * @param $data
     *
     * @return bool
     */
    public function onReceive(SwooleServer $server, $fd, $from_id, $data)
    {

    }

    /**
     * task进程，调用用户的方法进行处理
     *
     * @param SwooleServer $server
     * @param integer $task_id
     * @param integer $from_id
     * @param array $data
     *
     * @return mixed
     */
    public function onTask(SwooleServer $server, $task_id, $from_id, $data)
    {

    }

    /**
     * task完成后的处理方法
     *
     * @param SwooleServer $server
     * @param integer $task_id
     * @param array $data 处理后的回调参数
     *
     * @return bool
     */
    public function onFinish(SwooleServer $server, $task_id, $data)
    {

    }

    /**
     * 定时注册服务
     * 监控这个代理机器是否正常
     *
     * @param SwooleProcess $process
     */
    public function register(SwooleProcess $process)
    {
        $this->setProcessName('MonitorReport');
        $redis = RedisClient::getInstance();
        $field = SERVER_INTERNAL_IP . ':' . SERVER_PORT;
        $monitorKey = Constants::REDIS_KEY_AGENT_SERVER_LIST;
        // 上报的服务器IP
        while (true) {
            // hash表存放最后上报时间
            $redis->hset($monitorKey, $field, ['time' => time()]);
            $redis->expire($monitorKey, 86400);
            sleep(10);
            //sleep 10 sec and report again
        }
    }

    public function __call($func, $params)
    {
        return call_user_func_array([$this->sw, $func], $params);
    }

    public function onShutdown(SwooleServer $server)
    {
        if (!empty($this->_config['pid_file']) && file_exists($this->_config['pid_file'])) {
            unlink($this->_config['pid_file']);
        }
    }
}

class ServerOptionException extends \Exception
{

}
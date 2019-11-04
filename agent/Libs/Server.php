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
    const TASK_WORKER_NUM = 5;

    /**
     * $worker_id是一个从0 - $worker_num - 1之间的数字，表示这个Worker进程的ID
     * 所以Task进程的Id是taskWorkerId - $worker_num 到 $task_worker_num - 1之间的数字，表示这个Task Worker进程的ID
     */
    const TASK_LOAD_TASKS = 0; // 从数据库中载入所有任务，监听是否有变更
    const TASK_PARSE_TASKS = 1;// 分析所有任务，把这一分钟的任务载入任务进程
    const TASK_CLEAN_TASKS = 2; // 清理已执行过的任务, 失败超时的任务
    const TASK_GET_TASKS = 3; // 每一秒获取当前可以执行的任务
    const TASK_MANAGER_TASKS = 4;// 管理task状态

    const WORKER_EXEC_TASKS = 0;//创建进程执行任务

    /**
     * Worker Task Start
     * @var array
     */
    public $_initTaskMaps = [];

    // 状态进程Pid文件
    public static $statsPidFile;

    // 接收数据处理方法映射数组
    protected $_receiveModeProcessMaps = array(
        Constants::SW_CONTROL_CMD => 'controlCommand',
        Constants::SW_API_CMD     => 'apiCommand',
    );

    protected static $_defaultOptions = [
        'd|daemon'  => '启用守护进程模式',
        'h|host?'   => '指定监听地址',
        'p|port?'   => '指定监听端口',
        'help'      => '显示帮助界面',
//        'b|base'    => '使用BASE模式启动',
//		'w|worker?' => '设置Worker进程的数量',
        'r|thread?' => '设置Reactor线程的数量',
//		't|tasker?' => '设置Task进程的数量',
    ];

    /**
     * SwooleServer constructor.
     *
     * @param string $host
     * @param int $port
     * @param bool $ssl 是否开启安全加密
     *
     * @throws \Exception
     */
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
                $server->tick(configItem('monitor_reload_interval', 10) * 1000, function () use ($server) {
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
            // 清理已执行过的任务, 失败超时的任务
            self::TASK_CLEAN_TASKS   => function ($server) {
                $this->setProcessName("Task|CleanTask");
                $server->tick(60000, function () use ($server) {
                    Tasks::clean();
                });
            },
            // 获取这一秒要执行的任务，转发给执行任务的worker进程(task进程不支持asyncIo, 无法监听管道是否可读)
            self::TASK_GET_TASKS     => function ($server) {
                $this->setProcessName("Task|SendWorker");
                $dstWorkerId = self::WORKER_EXEC_TASKS;
                $server->tick(1000, function () use ($server, &$dstWorkerId) {
                    $tasks = Tasks::getTasks();
                    if ($tasks) {
                        /**
                         * 轮询发送
                         * 在Task进程内调用sendMessage是阻塞等待的，发送消息完成后返回
                         * task_ipc_mode SW_IPC_UNIXSOCK 使用swSocket_write_blocking方法发送(write fd)
                         * https://github.com/swoole/swoole-src/blob/4d7b6665998a8f58a292149afce0863ba966744c/src/core/socket.c#L175:5
                         * TODO 本身发送很快，但是也有可能会与到极端情况导致超过1秒，导致后续任务不准时
                         */
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
                /**
                 * @see Server::initServer()
                 */
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
        if (!empty(self::$_options['daemon'])) {
            $this->_config['daemonize'] = true;
        }

        $this->_sw->on('Start', [$this, 'onMasterStart']);
        $this->_sw->on('ManagerStart', [$this, 'onManagerStart']);
        $this->_sw->on('Shutdown', [$this, 'onShutdown']);
        $this->_sw->on('ManagerStop', [$this, 'onManagerStop']);
        $this->_sw->on('WorkerStart', [$this, 'onWorkerStart']);
        $this->_sw->on('Connect', [$this, 'onConnect']);
        $this->_sw->on('Receive', [$this, 'onReceive']);
        $this->_sw->on('PipeMessage', [$this, 'onPipeMessage']);
        $this->_sw->on('Close', [$this, 'onClose']);
        $this->_sw->on('WorkerStop', [$this, 'onWorkerStop']);

        if (is_callable([$this, 'onTask'])) {
            $this->_sw->on('Task', [$this, 'onTask']);
            $this->_sw->on('Finish', [$this, 'onFinish']);
        }

        parent::run();
    }

    /**
     * 服务启动前做一些初始化操作
     *
     * @return mixed
     */
    protected function initServer()
    {
        LoadTasks::init(); // 载入crontab表符合条件的记录
        Donkeyid::init(); // 初始化donkeyid对象
        Process::init(); //载入任务进程处理表，目前有哪些进程在执行任务
        Tasks::init(); // 载入任务表，当前这一分钟要执行的任务

        // 新增监控告警进程
        Report::init(); // 告警模块
        $monitorAlarmProcessNum = configItem('monitor_alarm_process_num', 1);
        for ($i = 0; $i < $monitorAlarmProcessNum; $i++) {
            $this->_sw->addProcess(new SwooleProcess(function ($process) use ($i) {
                $this->setProcessName('monitorAlarm|' . $i, null, $process);
                Report::monitorAlarm();
            }));
        }

        // DB日志进程，异步刷日志到数据库
        DbLog::init();
        $this->_sw->addProcess(new SwooleProcess(function ($process) {
            $this->setProcessName('logFlushToDB', null, $process);
            DbLog::flush();
        }));

        // 如果配置了主进程PID文件路径，就把主进程PID写入文件，以供调用
        if (self::$statsPidFile) {
            $this->_sw->addProcess(new SwooleProcess(function ($process) {
                $this->setProcessName('listenPipeProcess', null, $process);
                // 把pid写入文件，给其它地方使用
                file_put_contents(self::$statsPidFile, $process->pid);

                // 捕获用户自定义信号，用来打印当前状态信息
                SwooleProcess::signal(SIGUSR1, function ($sig) {
                    $tasks = LoadTasks::getTable();
                    $content = 'The node has no tasks' . PHP_EOL;
                    if (count($tasks) > 0) {
                        $content = '';
                        foreach ($tasks as $id => $task) {
                            $content .= 'id = ' . $id . '; task = ' . var_export($task, true) . PHP_EOL;
                        }
                    }

                    // 当前待执行的任务表
                    $tasks = Tasks::$table;
                    $taskCount = count($tasks);
                    if ($taskCount > 0) {
                        $content .= 'Task to be performed:' . $taskCount . PHP_EOL;
                        foreach ($tasks as $task) {
                            $content .= 'taskId = ' . $task['taskId'] . '; exec time = ' . date('Y-m-d H:i:s', $task['sec']) . '; retries = ' . $task['retries'] . PHP_EOL;
                        }
                    }
                    self::formatOutput($content);
                });
            }));
        }

        parent::initServer();

        // 连接中心服注册服务
        $this->_sw->addProcess(new SwooleProcess([$this, 'register']));
    }


    /**
     * 主进程启动的时候触发
     *
     * @param SwooleServer $server
     */
    public function onMasterStart(SwooleServer $server)
    {
        $this->setProcessName(': master -host=' . $this->_host . ' -port=' . $this->_port, '');
        $this->formatOutput("Master PID = {$server->master_pid}");
        $this->formatOutput("Manager PID = {$server->manager_pid}");
        $this->formatOutput("Swoole Version = [" . SWOOLE_VERSION . "]");
        $this->formatOutput("Server IP = " . SERVER_INTERNAL_IP);
        $this->formatOutput("Listen IP = {$this->_host}");
        $this->formatOutput("Listen Port = {$this->_port}");
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
            Process::signal($server);//注册信号
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
                $taskId = $item['taskId'];
                // 当前执行任务的时间戳
                $sec = $item['sec'];
                $task = $loadTasksTable->get($taskId);
                // 这个任务存在，如果被删除了，就不再执行
                if ($task) {
                    $agentName = LoadTasks::getAgentName();
                    $msg = '任务名称: ' . $task['name'] . PHP_EOL
                        . '指定执行时间: ' . date($this->dateFormat, $sec) . PHP_EOL
                        . '执行节点: ' . $agentName;
                    $tmp = [
                        'taskId'  => $taskId,
                        'command' => $task['command'],
                        'agents'  => $task['agents'],
                        'name'    => $task['name'],
                        'execNum' => $task['execNum'],
                        'runUser' => $task['runUser'],
                        'sec'     => $sec,
                        'runId'   => $runId,
                        'retries' => 0, // 当前第几次重试
                        'logOpt'  => $task['logOpt'],
                    ];
                    // 当前第几次重试
                    if (isset($item['currentRetries'])) {
                        $tmp['retries'] = $item['currentRetries'];
                        $msg = '第' . $item['currentRetries'] . '次重试' . PHP_EOL . $msg;
                    }

                    // 正在运行标示
                    if (Tasks::$table->exist($runId)) {
                        Tasks::$table->set($runId, [
                            'runStatus' => LoadTasks::RUN_STATUS_START,
                            'runId'     => $runId,
                            // 初始化，以免重试的时候值没有变更
                            'pid'       => 0,
                        ]);
                    }

                    DbLog::log($tmp["runId"], $taskId, Constants::CUSTOM_CODE_READY_START, "任务准备开始", $msg);
                    $ret[$runId] = [
                        'taskId' => $taskId,
                        'ret'    => Process::create_process($tmp) // 创建进程准备执行
                    ];
                }
            }
            $server->sendMessage($ret, $server->setting['worker_num'] + self::TASK_MANAGER_TASKS);
        } else if ($workerId == ($server->setting['worker_num'] + self::TASK_MANAGER_TASKS)) {
            foreach ($data as $runId => $v) {
                $taskId = $v['taskId'];
                $title = '创建进程失败';
                $code = Constants::CUSTOM_CODE_CREATE_CHILD_PROCESS_FAILED;
                $logMethod = 'endLog';
                if ($v["ret"]) {
                    $title = '创建进程成功';
                    $code = Constants::CUSTOM_CODE_CREATE_CHILD_PROCESS_SUCCESS;
                    $logMethod = 'log';
                } else {
                    Report::taskCreateProcessFailed($taskId, $runId);//报警
                }
                DbLog::{$logMethod}($runId, $taskId, $code, $title);
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
        $data = Packet::packDecode($data);
        // Decode Error
        if ($data["code"] != Constants::STATUS_CODE_SUCCESS) {
            $req = Packet::packEncode($data);
            $this->send($fd, $req);
            return true;
        }
        $data = $data['data'];
        // Cmd not set
        if (empty($data["cmd"])) {
            $pack = Packet::packFormat("invalid request", Constants::STATUS_CODE_MISS_CMD_PARAM);
            $this->send($fd, $pack);
            return true;
        }

        $task = [
            "type"    => $data["type"],
            "fd"      => $fd,
            'from_id' => $from_id,
        ];

        // 是否有消息类型处理方法
        if (isset($this->_receiveModeProcessMaps[$data['type']])) {
            call_user_func_array([
                $this,
                $this->_receiveModeProcessMaps[$data['type']]
            ], [$server, $task, $data]);
        } else {
            $pack = Packet::packFormat("unknown task type", Constants::STATUS_CODE_UNKNOW_TASK_TYPE);
            $this->send($fd, $pack);
        }
        return true;
    }

    /**
     * 接收处理模式: 服务控制阻塞型请求等待返回结果
     *
     * @param object $server Swoole Server Object
     * @param array $task task进程需要的参数
     * @param array $data 请求参数
     *
     * @return bool
     */
    public function controlCommand($server, $task, $data)
    {
        $data['cmd'] = strtoupper($data['cmd']);

        $pack = Packet::packFormat('unknown command!', Constants::STATUS_CODE_UNKNOW_CMD);

        if ($data['cmd'] === 'PING') {
            $pack = Packet::packFormat('PONG', Constants::STATUS_CODE_SUCCESS);
        }
        return $this->send($task['fd'], $pack);
    }

    /**
     * 接收处理模式: 客户端请求阻塞型请求等待返回结果
     *
     * @param object $server Swoole Server Object
     * @param array $task task进程需要的参数
     * @param array $data 请求参数
     *
     * @return bool
     */
    public function apiCommand($server, $task, $data)
    {
        return true;
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
            $fieldValue = [
                'time'               => time(),
                // 本节点总任务数
                'task_total'         => count(LoadTasks::getTable()),
                // 当前这一分钟待执行的任务数
                'current_task_count' => count(Tasks::getTasks()),
            ];
            if (isLinuxOS()) {
                $fieldValue = array_merge($fieldValue, [
                    'cpuInfo'    => getCoreInformation(),
                    'sysLoadAvg' => sys_getloadavg(),
                    'memInfo'    => getMemoryInformation([
                        'MemTotal',
                        'MemFree',
                        'Buffers',
                        'Cached',
                        'SwapCached',
                        'SwapTotal',
                        'SwapFree',
                    ]),
                ]);
            }
            // hash表存放最后上报时间
            $redis->hset($monitorKey, $field, $fieldValue);
            $redis->expire($monitorKey, 86400);
            sleep(10);
            //sleep 10 sec and report again
        }
    }

    public function send($client_id, $data)
    {
        $data = Packet::packEncode($data);
        return $this->_sw->send($client_id, $data);
    }

    public function __call($func, $params)
    {
        return call_user_func_array([$this->_sw, $func], $params);
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
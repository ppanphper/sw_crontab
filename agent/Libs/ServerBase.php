<?php

namespace Libs;

use \Swoole\Server as SwooleServer;
use \Swoole\Process as SwooleProcess;

/**
 * Created by PhpStorm.
 * User: pandy
 * Date: 2018/04/23
 * Time: 14:49
 */

/**
 * Created by PhpStorm.
 * User: pandy
 * Date: 2016/11/23
 * Time: 19:49
 */
abstract class ServerBase
{
    protected static $options = array();
    /**
     * SwooleServer对象
     * @var null|SwooleServer
     */
    protected $sw = null;

    /**
     * 默认日期格式
     *
     * @var mixed|null
     */
    public $dateFormat;

    protected $host = null;

    protected $port = null;

    protected $ssl = false;

    protected static $beforeStopCallback;
    protected static $beforeReloadCallback;

    public static $swooleMode;
    public static $optionKit;
    public static $pidFile;

    public static $defaultOptions = array(
        'd|daemon'  => '启用守护进程模式',
        'h|host?'   => '指定监听地址',
        'p|port?'   => '指定监听端口',
        'help'      => '显示帮助界面',
        'b|base'    => '使用BASE模式启动',
//		'w|worker?' => '设置Worker进程的数量',
        'r|thread?' => '设置Reactor线程的数量',
//		't|tasker?' => '设置Task进程的数量',
    );

    /**
     * 连接池名称
     * @var string
     */
    protected $_serverName = '';

    /**
     * Swoole配置
     * @var array
     */
    protected $_config = [];

    protected $_levels = array(
        E_ERROR             => 'Error',
        E_WARNING           => 'Warning',
//		E_PARSE           => 'Parsing Error',
        E_PARSE             => 'Error',
        E_NOTICE            => 'Notice',
        E_CORE_ERROR        => 'Core Error',
        E_CORE_WARNING      => 'Core Warning',
        E_COMPILE_ERROR     => 'Compile Error',
        E_COMPILE_WARNING   => 'Compile Warning',
        E_USER_ERROR        => 'User Error',
        E_USER_WARNING      => 'User Warning',
        E_USER_NOTICE       => 'User Notice',
        E_STRICT            => 'Runtime Notice',
        E_RECOVERABLE_ERROR => 'Fatal Error',
    );

    protected static $_startMethodMaps;

    /**
     * SwooleServer constructor.
     *
     * @param string $host
     * @param int $port
     * @param bool $ssl 是否开启安全加密
     *
     * @throws \Exception
     */
    public function __construct($host = "0.0.0.0", $port = 0, $ssl = false)
    {
        $swooleVersion = configItem('swoole_version');
        if (version_compare(SWOOLE_VERSION, $swooleVersion, '<')) {
            die('请安装Swoole ' . $swooleVersion . ' 以上的版本!');
        }

        $flag = $ssl ? (SWOOLE_SOCK_TCP | SWOOLE_SSL) : SWOOLE_SOCK_TCP;
        if (!empty(self::$options['base'])) {
            self::$swooleMode = SWOOLE_BASE;
        } elseif (extension_loaded('swoole')) {
            self::$swooleMode = SWOOLE_PROCESS;
        }

        $this->sw = new SwooleServer($host, $port, self::$swooleMode, $flag);

        //store current ip port
        $this->host = $host;
        $this->port = $this->sw->port;
        $this->ssl = $ssl;

        // 定义内部IP和端口常量
        !defined('SERVER_INTERNAL_IP') && define('SERVER_INTERNAL_IP', getServerInternalIp());
        !defined('SERVER_PORT') && define('SERVER_PORT', $this->port);

        $this->dateFormat = configItem('default_date_format', 'Y-m-d H:i:s');

        $this->_config = [
            'dispatch_mode'            => 3,
            'package_length_type'      => 'N',
            'package_length_offset'    => 0,
            'package_body_offset'      => 4,
            'open_length_check'        => 1,
            /**
             * 设置最大数据包尺寸，单位为字节。开启open_length_check/open_eof_check/open_http_protocol等协议解析后。swoole底层会进行数据包拼接。这时在数据包未收取完整时，所有数据都是保存在内存中的。
             * 所以需要设定package_max_length，一个数据包最大允许占用的内存尺寸。如果同时有1万个TCP连接在发送数据，每个数据包2M，那么最极限的情况下，就会占用20G的内存空间。
             */
            'package_max_length'       => 1024 * 1024 * 2,
            /**
             * 发送缓存区尺寸
             * 调用swoole_server->send， swoole_http_server->end/write，swoole_websocket_server->push 时，单次最大发送的数据不得超过buffer_output_size配置。
             * 开启大量worker进程时，将会占用worker_num * buffer_output_size字节的内存
             * 1000个Worker进程 = 3GB
             */
            'buffer_output_size'       => 1024 * 1024 * 3,
            'open_tcp_nodelay'         => 1,
            'heartbeat_check_interval' => 30, // 30秒检测一次心跳
            'heartbeat_idle_time'      => 180, // 3分钟客户端没有发送请求，关闭连接
            'open_cpu_affinity'        => 1,

            'worker_num'      => 10,
            'task_worker_num' => 4,

            'max_request'      => 0, //必须设置为0否则并发任务容易丢,don't change this number
            'task_max_request' => 0, // 不退出

            /**
             * Listen队列长度，如backlog => 128，此参数将决定最多同时有多少个等待accept的连接。
             */
            'backlog'          => 20000,
            'log_file'         => LOGS_PATH . 'sw_server.log',
            'daemonize'        => 0,
        ];

        set_error_handler([$this, '_error_handler']);
    }

    /**
     * 服务启动前的初始化
     */
    public static function init()
    {
        self::$_startMethodMaps = [
            'start'   => function ($serverPID, $opt) {
                //已存在ServerPID，并且进程存在
                if (!empty($serverPID) and posix_kill($serverPID, 0)) {
                    exit("Server is already running.\n");
                }
            },
            'restart' => function ($serverPID, $opt) {
                //已存在ServerPID，并且进程存在
                if (!empty($serverPID) and posix_kill($serverPID, 0)) {
                    if (self::$beforeStopCallback) {
                        call_user_func(self::$beforeStopCallback, $opt);
                    }
                    posix_kill($serverPID, SIGTERM);
                    self::formatOutput('Stopped');
                }
            },
            'reload'  => function ($serverPID, $opt) {
                if (empty($serverPID)) {
                    exit("Server is not running");
                }
                if (self::$beforeReloadCallback) {
                    call_user_func(self::$beforeReloadCallback, $opt);
                }
                posix_kill($serverPID, SIGUSR1);
                exit(0);
            },
            'stop'    => function ($serverPID, $opt) {
                if (empty($serverPID)) {
                    exit("Server is not running\n");
                }
                if (self::$beforeStopCallback) {
                    call_user_func(self::$beforeStopCallback, $opt);
                }
                posix_kill($serverPID, SIGTERM);
                exit(0);
            }
        ];
    }

    /**
     * 服务配置启动的入口
     *
     * @param callable $startFunction
     *
     * @throws \Exception
     */
    public static function start(callable $startFunction)
    {
        if (empty(self::$pidFile)) {
            throw new \Exception("require pidFile.");
        }
        $pid_file = self::$pidFile;
        if (is_file($pid_file)) {
            $serverPID = file_get_contents($pid_file);
        } else {
            $serverPID = 0;
        }

        if (!self::$optionKit) {
            Loader::addNameSpace('GetOptionKit', LIBS_PATH . "GetOptionKit/src/GetOptionKit");
            self::$optionKit = new \GetOptionKit\GetOptionKit;
        }

        $kit = self::$optionKit;
        foreach (self::$defaultOptions as $k => $v) {
            //解决Windows平台乱码问题
            if (PHP_OS == 'WINNT') {
                $v = iconv('utf-8', 'gbk', $v);
            }
            $kit->add($k, $v);
        }
        global $argv;
        $opt = $kit->parse($argv);
        if (empty($argv[1]) or isset($opt['help']) || !isset(self::$_startMethodMaps[$argv[1]])) {
            usage:
            $kit->specs->printOptions("php {$argv[0]} start|restart|stop|reload");
            exit(0);
        }
        call_user_func_array(self::$_startMethodMaps[$argv[1]], [$serverPID, $opt]);
        self::$options = $opt;
        self::formatOutput('Starting');
        $startFunction($opt);
    }

    public function run()
    {
        $this->sw->set($this->_config);
        $this->initServer();
        $this->sw->start();
    }

    public function setServerName($name)
    {
        $this->_serverName = $name;
        return $this;
    }

    /**
     * 设置进程名称
     *
     * @param $name
     * @param string $separator
     * @param SwooleProcess $process
     */
    public function setProcessName($name, $separator = '|', $process = null)
    {
        if (is_null($separator)) {
            $separator = '|';
        }

        // Mac不支持设置进程名称
        if (isMacOS()) {
            return;
        }

        $processNewName = $this->_serverName . $separator . $name;
        if ($process instanceof SwooleProcess) {
            $process->name($processNewName);
            return;
        }

        if (function_exists('cli_set_process_title')) {
            cli_set_process_title($processNewName);
        } else {
            swoole_set_process_name($processNewName);
        }
    }

    /**
     * Swoole服务启动
     *
     * @param SwooleServer $server
     */
    public function onMasterStart(SwooleServer $server)
    {
        $this->setProcessName("Master");
        $this->formatOutput("Master PID = {$server->master_pid}");
        $this->formatOutput("Manager PID = {$server->manager_pid}");
        $this->formatOutput("Swoole Version = [" . SWOOLE_VERSION . "]");
        $this->formatOutput("Listen IP = {$this->host}");
        $this->formatOutput("Listen Port = {$this->port}");
        $this->formatOutput("Reactor Number = {$server->setting['reactor_num']}");
        $this->formatOutput("Worker Number = {$server->setting['worker_num']}");
        $this->formatOutput("Task Number = {$server->setting['task_worker_num']}");
    }

    /**
     * 管理进程启动时
     *
     * @param SwooleServer $server
     */
    public function onManagerStart(SwooleServer $server)
    {
        $this->setProcessName("Manager");
    }

    /**
     * 管理进程停止时
     *
     * @param SwooleServer $server
     */
    public function onManagerStop(SwooleServer $server)
    {
        $this->formatOutput("Manager Stop , shutdown server");
        $server->shutdown();
    }

    public function onShutdown(SwooleServer $server)
    {

    }

    /**
     * @param SwooleServer $server
     * @param int $fd 值范围:1~1600万 fd是tcp连接的文件描述符，在swoole_server中是客户端的唯一标识符
     * @param int $from_id 是来自于哪个reactor线程
     */
    public function onConnect(SwooleServer $server, $fd, $from_id)
    {
    }

    /**
     * @param SwooleServer $server
     * @param int $fd 值范围:1~1600万 fd是tcp连接的文件描述符，在swoole_server中是客户端的唯一标识符
     * @param int $from_id 是来自于哪个reactor线程
     */
    public function onClose(SwooleServer $server, $fd, $from_id)
    {
    }

    /**
     * Worker/Task进程启动的时候做一些初始化操作
     *
     * @param SwooleServer $server
     * @param $worker_id
     */
    public function onWorkerStart(SwooleServer $server, $worker_id)
    {
        $isTask = $server->taskworker;
        if (!$isTask) {
            //worker
            $this->setProcessName("Worker|{$worker_id}");
            $this->initWork($server, $worker_id);
        } else {
            //task
            $this->setProcessName("Task|{$worker_id}");
            $this->initTask($server, $worker_id);
        }
    }

    /**
     * Worker和Task进程结束时，会调用此方法
     *
     * @param $server
     * @param $worker_id
     */
    public function onWorkerStop($server, $worker_id)
    {

    }

    /**
     * 当worker/task_worker进程发生异常后会在Manager进程内回调此函数。
     *
     * @param SwooleServer $server
     * @param int $worker_id
     * @param int $worker_pid
     * @param int $exit_code
     */
    public function onWorkerError(SwooleServer $server, $worker_id, $worker_pid, $exit_code)
    {

    }

    /**
     * 服务启动时做一些初始化操作
     *
     * @return mixed
     */
    protected function initServer()
    {
        /**
         * 日志进程，异步刷日志到文件
         */
        Log::init();
        $this->sw->addProcess(new SwooleProcess(function ($process) {
            if (!isMacOS()) {
                $process->name($this->_serverName . '|LogFlushToDisk');
            }
            return Log::flush();
        }));
        /** End */
    }

    /**
     * Worker进程启动时做一些初始化操作
     *
     * @param SwooleServer $server
     * @param $worker_id
     */
    protected function initWork(SwooleServer $server, $worker_id)
    {
    }

    /**
     * Task进程启动时做一些初始化操作
     *
     * @param SwooleServer $server
     * @param $worker_id
     */
    protected function initTask(SwooleServer $server, $worker_id)
    {
    }

    /**
     * 设置PID文件
     *
     * @param $pidFile
     */
    public static function setPidFile($pidFile)
    {
        self::$pidFile = $pidFile;
    }

    /**
     * 杀死所有进程
     *
     * @param $name
     * @param int $signo
     *
     * @return string
     */
    public static function killProcessByName($name, $signo = 9)
    {
        $cmd = 'ps -eaf |grep "' . $name . '" | grep -v "grep"| awk "{print $2}"|xargs kill -' . $signo;
        return exec($cmd);
    }

    /**
     *
     * $opt->add( 'f|foo:' , 'option requires a value.' );
     * $opt->add( 'b|bar+' , 'option with multiple value.' );
     * $opt->add( 'z|zoo?' , 'option with optional value.' );
     * $opt->add( 'v|verbose' , 'verbose message.' );
     * $opt->add( 'd|debug'   , 'debug message.' );
     * $opt->add( 'long'   , 'long option name only.' );
     * $opt->add( 's'   , 'short option name only.' );
     *
     * @param $specString
     * @param $description
     *
     * @throws ServerOptionException
     */
    public static function addOption($specString, $description)
    {
        if (!self::$optionKit) {
            Loader::addNameSpace('GetOptionKit', LIBS_PATH . "GetOptionKit/src/GetOptionKit");
            self::$optionKit = new \GetOptionKit\GetOptionKit;
        }
        foreach (self::$defaultOptions as $k => $v) {
            if ($k[0] == $specString[0]) {
                throw new ServerOptionException("不能添加系统保留的选项名称");
            }
        }
        //解决Windows平台乱码问题
        if (PHP_OS == 'WINNT') {
            $description = iconv('utf-8', 'gbk', $description);
        }
        self::$optionKit->add($specString, $description);
    }

    /**
     * @param callable $function
     */
    public static function beforeStop(callable $function)
    {
        self::$beforeStopCallback = $function;
    }

    /**
     * @param callable $function
     */
    public static function beforeReload(callable $function)
    {
        self::$beforeReloadCallback = $function;
    }

    public function daemonize()
    {
        $this->_config['daemonize'] = 1;
    }

    public function connection_info($fd)
    {
        return $this->sw->connection_info($fd);
    }

    public function close($client_id)
    {
        return $this->sw->close($client_id);
    }

    public function send($client_id, $data)
    {
        return $this->sw->send($client_id, $data);
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
    abstract public function onReceive(SwooleServer $server, $fd, $from_id, $data);

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
    abstract public function onTask(SwooleServer $server, $task_id, $from_id, $data);

    /**
     * task完成后的处理方法
     *
     * @param SwooleServer $server
     * @param integer $task_id
     * @param array $data 处理后的回调参数
     *
     * @return bool
     */
    abstract public function onFinish(SwooleServer $server, $task_id, $data);

    public static function formatOutput($msg, $isReturn = false)
    {
        $msg = "[" . Log::uDate() . "] " . $msg . PHP_EOL;
        if ($isReturn)
            return $msg;
        echo $msg;
    }

    /**
     * 错误捕获
     *
     * @param $severity
     * @param $message
     * @param $filePath
     * @param $line
     */
    public function _error_handler($severity, $message, $filePath, $line)
    {
        // We don't bother with "strict" notices since they tend to fill up
        // the log file with excess information that isn't normally very helpful.
        if ($severity == E_STRICT) {
            return;
        }
        $msg = $message . ' at ' . $filePath . '[' . $line . ']';
        logMessage($this->_levels[$severity], $msg);
    }
}
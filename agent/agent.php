<?php
/**
 * Created by PhpStorm.
 * User: pandy
 * Date: 2018/4/23
 * Time: 下午2:54
 */

namespace Agent;

use function Libs\config_item;
use Libs\Loader;
use Libs\LoadTasks;
use Libs\Tasks;
use Libs\Server;
use Libs\Process;
use Libs\Donkeyid;

if (file_exists(dirname(__FILE__) . '/env.php')) include_once dirname(__FILE__) . '/env.php';//载入环境变量

define('ROOT_PATH', __DIR__ . '/');
define('LIBS_PATH', ROOT_PATH . 'Libs/');
define('CONFIG_PATH', ROOT_PATH . 'Config/');
define('LOGS_PATH', ROOT_PATH . 'Logs/');
define('TPL_PATH', ROOT_PATH . 'Tpl/');
define('ENVIRONMENT', !empty($envInit['env']) ? $envInit['env'] : 'prod');

//重定向PHP错误日志到logs目录
ini_set('error_log', LOGS_PATH . '/php_errors.log');

if (!file_exists(LIBS_PATH . 'Common.php')) {
    exit('Common.php is not found!');
}
require_once LIBS_PATH . 'Common.php';

//最多载入任务数量
define('TASK_MAX_LOAD_SIZE', config_item('task_max_load_size', 8192));
// 最大进程数
define('PROCESS_MAX_SIZE', config_item('process_max_size', 1024));
// 同时运行任务最大数量
define('TASKS_MAX_CONCURRENT_SIZE', config_item('task_max_concurrent_size', 1024));

if (!class_exists('Agent\\Libs\\Loader')) {
    $autoloadPath = LIBS_PATH . 'Loader.php';
    if (!file_exists($autoloadPath)) {
        exit('Agent library autoloader is not found!');
    }
    require_once $autoloadPath;
    Loader::addNameSpace('Libs', LIBS_PATH);
    Loader::register();
}

require __DIR__ . '/vendor/autoload.php';

Server::setPidFile(LOGS_PATH . '/agent_' . config_item('server_listen_port', 8901) . '.pid');
Server::setStatsPidFile(LOGS_PATH . '/agent_' . config_item('server_listen_port', 8901) . '_stats.pid');
Server::init();
Server::start(function ($opt) {

    $listenHost = config_item('server_listen_host', '0.0.0.0');
    $listenPort = config_item('server_listen_port', 8901);

    LoadTasks::init();// 载入crontab表符合条件的记录
    Donkeyid::init();//初始化donkeyid对象
    Process::init();//载入任务进程处理表，目前有哪些进程在执行任务
    Tasks::init();// 载入任务表，当前这一分钟要执行的任务

    $server = new Server($listenHost, $listenPort);
    $server->setServerName("AgentServer");
    $server->run();
});
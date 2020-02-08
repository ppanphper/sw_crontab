<?php
/**
 * Created by PhpStorm.
 * User: pandy
 * Date: 2018/4/23
 * Time: 下午2:54
 */

use function Libs\configItem;
use Libs\Loader;
use Libs\Server;

if (file_exists(dirname(__FILE__).'/env.php'))
    $envInit = include(dirname(__FILE__).'/env.php');//载入环境变量

mb_internal_encoding('UTF-8');

define('ROOT_PATH', __DIR__ . '/');
define('LIBS_PATH', ROOT_PATH . 'Libs/');
define('MODELS_PATH', ROOT_PATH . 'Models/');
define('CONFIG_PATH', ROOT_PATH . 'Config/');
define('TPL_PATH', ROOT_PATH . 'Tpl/');
define('ENVIRONMENT', !empty($envInit['env']) ? $envInit['env'] : 'prod');

if (!file_exists(LIBS_PATH . 'Common.php')) {
    exit('Common.php is not found!');
}
require_once LIBS_PATH . 'Common.php';

$logConfig = configItem('log');
$logPath = rtrim(!empty($logConfig['path']) ? $logConfig['path'] : ROOT_PATH . 'Logs', DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
define('LOGS_PATH', $logPath);
//重定向PHP错误日志到logs目录
ini_set('error_log', LOGS_PATH . 'php_errors.log');

// 最多载入任务数量
define('TASK_MAX_LOAD_SIZE', configItem('task_max_load_size', 8192));
// 最大进程数
define('PROCESS_MAX_SIZE', configItem('process_max_size', 1024));
// 一分钟内运行任务最大数量
define('TASKS_MAX_CONCURRENT_SIZE', configItem('task_max_concurrent_size', 8192));
// 日志临时存储最大条数
define('LOG_TEMP_STORE_MAX_SIZE', configItem('log_temp_store_max_size', 16384));

if (!class_exists('Agent\\Libs\\Loader')) {
    $autoloadPath = LIBS_PATH . 'Loader.php';
    if (!file_exists($autoloadPath)) {
        exit('Agent library autoloader is not found!');
    }
    require_once $autoloadPath;
    Loader::addNameSpace('Libs', LIBS_PATH);
    Loader::addNameSpace('Models', MODELS_PATH);
    Loader::register();
}

require __DIR__ . '/vendor/autoload.php';

Server::init();
Server::start(function ($opt) {

    $listenHost = configItem('server_listen_host', '0.0.0.0');
    $listenPort = configItem('server_listen_port', 8901);

    $server = new Server($listenHost, $listenPort);
    $server->setServerName("AgentServer");
    $server->run();
});
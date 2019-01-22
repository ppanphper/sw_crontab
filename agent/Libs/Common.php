<?php
namespace Libs;
use \Exception;

/**
 * Created by PhpStorm.
 * User: pandy
 * Date: 2017/1/17
 * Time: 15:08
 */
if(!defined('ROOT_PATH')) throw new Exception('ROOT_PATH常量未定义');
if (version_compare(PHP_VERSION, '5.6.0') < 0) throw new Exception('PHP Version must be above 5.6 !');

/**
 * @param string $level
 * @param $message
 * @param null $exceptionObject
 */
function log_message($level='Info', $message, $exceptionObject=null) {
    Log::write_log($level, $message, $exceptionObject);
}

/**
 * @param $message
 * @param null $exceptionObject
 */
function log_error($message, $exceptionObject=null) {
    log_message('Error', $message, $exceptionObject);
}

/**
 * @param $message
 * @param null $exceptionObject
 */
function log_warning($message, $exceptionObject=null) {
    log_message('Warning', $message, $exceptionObject);
}

/**
 * @param $message
 * @param null $exceptionObject
 */
function log_info($message, $exceptionObject=null) {
    log_message('Info', $message, $exceptionObject);
}

/**
 * 获取配置
 *
 * @param null|string $item
 * @param null|string $default 默认值
 * @param string $fileName 配置文件名称
 *
 * @return mixed|null
 */
function config_item($item=null, $default=null, $fileName='config') {
    static $_config = [];
    if(!isset($_config[$fileName])) {
        $filePaths = [
            ENVIRONMENT .'/' . $fileName .'.php',
            $fileName.'.php'
        ];
        $_config[$fileName] = [];
        foreach($filePaths as $filePath) {
            $configPath = CONFIG_PATH . $filePath;
            if(file_exists($configPath)) {
                $config = include($configPath);
                /**
                 * 1、不覆盖，只是追加不存在的键名和对应的值
                 * 2、键名不重新索引
                 * 3、无论是全部数字键名还是混合，都只是追加键名和值，如果键名相同则不进行追加，即把最先出现的值作为最终结果返回
                 */
                $_config[$fileName] += $config;
            }
        }
    }
    // 如果item为null，就返回整个配置文件
    if($item === null) {
        return $_config[$fileName];
    }
    return isset($_config[$fileName][$item]) ? $_config[$fileName][$item] : $default;
}

/**
 * 获取参数
 * @param mixed $arg
 * @param int $level
 * @return string
 */
function get_arg(&$arg, $level=0) {
    // 最大层数
    if($level >= TRACE_LEVEL) {
        return json_encode($arg);
    }
    if (is_object($arg)) {
        $arr = (array)$arg;
        $args = array();
        foreach($arr as $key => $value) {
            if (strpos($key, chr(0)) !== false) {
                $key = '';    // Private variable found
            }
            $level++;
            $args[] =  '['.$key.'] => ' . get_arg($value, $level);
        }

        $arg = get_class($arg) . ' Object ('.implode(',', $args).')';
    }
    return $arg;
}

/**
 * 遍历错误堆栈
 * @param $traces
 * @param int $traces_to_ignore
 * @param string $split
 * @param null $exceptionObject
 * @return string
 */
function convenienceDebug($traces, $traces_to_ignore = 0, $split=PHP_EOL, $exceptionObject=null) {
    if (($exceptionObject instanceof Exception || $exceptionObject instanceof \Throwable)){
        $traces = $exceptionObject->getTrace();
    }
    // 如果没有设置Trace的层数
    if (!defined("TRACE_LEVEL")) define("TRACE_LEVEL" , 3);
    // 是否显示详细的堆栈
    if (!defined("DEBUG_TRACE_DETAIL")) define("DEBUG_TRACE_DETAIL", TRUE);
    // 堆栈明细参数长度限制
    if (!defined("DEBUG_TRACE_DETAIL_PARAM_LENGTH")) define("DEBUG_TRACE_DETAIL_PARAM_LENGTH", 1024);
    $result = array();
    $count = 0;
    foreach($traces as $i => $trace){
        if ($i < $traces_to_ignore ) {
            continue;
        }
        // 取得函数或调用方法名称
        $object = isset($trace['class']) ? $trace['class'].$trace['type'] : '';
        // 序号
        $msg = '[#' . ($i - $traces_to_ignore) . '] ' . $object . $trace['function'];
        // 处理对象的参数
        if (isset($trace['class'])) {
            // 是否显示堆栈明细
            if (DEBUG_TRACE_DETAIL && is_array($trace['args'])) {
                foreach ($trace['args'] as &$arg) {
                    get_arg($arg);
                }
            }
        }
        // 处理换行符
        if (DEBUG_TRACE_DETAIL && isset($trace['args'][0]) && is_string($trace['args'][0]) && in_array(strtolower($trace['args'][0]), array('<br/>', PHP_EOL, '<br>', '</br>'))) {
            $trace['args'][0] = '';
        }
        // 调用的方法与参数
        if(DEBUG_TRACE_DETAIL) {
            $args = empty($trace['args']) ? '' : json_encode($trace['args']);
            $args = mb_strlen($args, 'UTF-8') > DEBUG_TRACE_DETAIL_PARAM_LENGTH ? mb_substr($args, 0, DEBUG_TRACE_DETAIL_PARAM_LENGTH, 'UTF-8').'...' : $args;
            $msg .= '('.$args.')';
        }
        else {
            $msg .= '()';
        }
        if(isset($trace['file'], $trace['line'])) {
            $msg .= ' in ' . $trace['file'] . '(' . $trace['line'] . ')';
        }
        $result[] = $msg;
        if(++$count >= TRACE_LEVEL)
            break;
    }
    $str = implode($split,$result).$split.$split.'--------------------------------------------------------------------------------'.$split;
    return $str;
}

/**
 * 输出php错误堆栈
 * @param string $split
 * @param null $exceptionObject
 * @return string
 */
function getBacktrace($split=PHP_EOL, $exceptionObject=null) {
    $arr = debug_backtrace();
    return convenienceDebug($arr, 0, $split, $exceptionObject);
}

if (!function_exists('createDir')) {
    /**
     * 创建目录
     *
     * @param string $path
     * @param int $mode
     *
     * @return bool
     */
    function createDir($path, $mode = 0744) {
        return is_dir($path) || (createDir(dirname($path), $mode) && mkdir($path, $mode));
    }
}

if(!function_exists('isLinuxOS')) {
    /**
     * 是否是Linux系统
     * @return bool
     */
    function isLinuxOS() {
        return stristr(PHP_OS, 'Linux') ? true : false;
    }
}
if(!function_exists('isWindowsOS')) {
    /**
     * 是否是windows系统
     * @return bool
     */
    function isWindowsOS()
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }
}

/**
 * 获取服务器内网IP
 * @return string
 */
function getServerInternalIp() {
    static $ip = '';
    if($ip) {
        return $ip;
    }
    $patternArray = array(
        '10\.',
        '172\.1[6-9]\.',
        '172\.2[0-9]\.',
        '172\.31\.',
        '192\.168\.'
    );
    try {
        $pattern = implode('|', $patternArray);
        // 如果有Swoole扩展
        if (extension_loaded('swoole')) {
            $serverIps = swoole_get_local_ip();
            foreach ($serverIps as $serverIp) {
                // 匹配内网IP
                if (preg_match('#^' . $pattern . '#', $serverIp)) {
                    $ip = $serverIp;
                    break;
                }
            }
        } else {
            // LINUX
            if (isLinuxOS()) {
                // 由于文件没有可执行权限，所以无法获取
                $ip = exec("/sbin/ifconfig|grep -oP '(?<=inet addr:)[^ ]+'|grep -E '^" . $pattern . "'|head -1");
            }
            // OSX
            else if (stristr(PHP_OS, 'DAR')) {
                $ip = exec("/sbin/ifconfig|grep -Eo 'inet (".$pattern.")[^ ]+|grep -Eo '(" . $pattern . ")[^ ]+'|head -1");
            }
            // WINDOWS
            else if (stristr(PHP_OS, 'WIN')) {
                exec("ipconfig /all", $ipInfo);
                foreach ($ipInfo as $line) {
                    // 匹配IPV4
                    if (preg_match('#\s*IPv4[^\d]+(\d+\.\d+\.\d+\.\d+).*#i', $line, $matches)) {
                        if (isset($matches[1])) {
                            $ip = $matches[1];
                            break;
                        }
                    }
                }
            }
        }
    } catch (Exception $e) {
        log_message('Warning', '获取服务器内网IP失败: ' . $e->getMessage());
    }
    if(!$ip) {
        // 获取不到内网IP
        $ip = '0.0.0.0';
    }
    return $ip;
}

/**
 * 秒、毫秒、微秒格式化
 *
 * @param int $time
 *
 * @return string
 */
function msTimeFormat($time) {
    if (!is_numeric($time)) return $time;
    if ($time >= 1) {
        return round($time, 2) . "s";
    }
    if ($time >= 0.001) {
        return round(($time * 1000), 2) . "ms";
    }
    if ($time < 0.001) {
        return round(($time * 1000000), 2) . "μs";
    }
    return $time . "s";
}

/**
 * 获取数据库实例
 *
 * @param string $name
 *
 * @return \Envms\FluentPDO\FluentPDO
 * @throws Exception
 */
function getDBInstance($name='') {
    static $_dbs = [];
    // 默认数据库
    empty($name) && $name = config_item('default_select_db');
    $name = strtolower($name);
    if(isset($_dbs[$name])) {
        return $_dbs[$name];
    }
    $dbConfig = config_item(null, null, 'db');
    $config = isset($dbConfig[$name]) ? $dbConfig[$name] : [];
    if(empty($config) || empty($config['dsn']) || empty($config['username'])) {
        throw new Exception('请先配置['.$name.']数据库连接参数');
    }
    $_dbs[$name] = \Envms\FluentPDO\Factory::create($config);
    if(isset($config['debug'])) {
        $_dbs[$name]->debug = $config['debug'];
    }
    return $_dbs[$name];
}

if(!function_exists('getConcurrentLock')) {
    /**
     * 获取、释放分布式锁
     *
     * @param $redisKeySuffix
     * @param int $type 1=lock 0=unlock
     * @param int $expireTime
     *
     * @return mixed
     * @throws Exception
     */
    function getConcurrentLock($redisKeySuffix, $type = 1, $expireTime=300) {
        static $serverInternalIp = null;
        if($serverInternalIp === null) {
            $serverInternalIp = getServerInternalIp(); // 获取服务器内部IP地址
        }
        if(!is_string($redisKeySuffix)) {
            throw new Exception('key必须是字符串类型');
        }

        if(strlen($redisKeySuffix) != 32) {
            $redisKeySuffix = md5($redisKeySuffix);
        }

        $redisObject = RedisClient::getInstance();
        $redisKey = Constants::REDIS_CONCURRENT_FLAG_PREFIX . $redisKeySuffix;
        // 尝试加锁
        if($type == 1) {
            /** 假如连不上Redis，那么所有机器都不运行这个脚本 */
            // 成功返回1，失败返回0; 锁定这个交易所，本次在这台机器上运行
            return $redisObject->set($redisKey, $serverInternalIp, ['NX', 'EX' => $expireTime]);
        }
        // 释放锁
        else {
            return $redisObject->del($redisKey);
        }
    }
}

/**
 * 新版的发送邮件函数
 * @param $title
 * @param $content
 * @param array|string $to ['姓名'=>'email地址'] 或 ['姓名','姓名2'] 或 '姓名' 或 'email地址'
 * @param array|string $cc ['姓名'=>'email地址'] 或 ['姓名','姓名2'] 或 '姓名' 或 'email地址'
 * @param array $file
 * @param int $retry 重试次数
 *
 * @return bool
 */
function sendMail($title, $content, $to, $cc= '', $file=[], $retry = 3) {
    static $config;

    $boolean = false;
    if(!$to || !$content) return $boolean;

    if(empty($config)) {
        $config = config_item(null, null, 'email');
    }
    $mail= new \PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mail->setLanguage($config['language']);
        $mail->isSMTP();
        $mail->CharSet = $config['charset'];
        $mail->Host = $config['host'];
        $mail->Port = $config['port'];
        $mail->SMTPSecure = $config['secure'];
        $mail->SMTPAuth = $config['auth'];
        $mail->Username = $config['username'];
        $mail->Password = $config['password'];
        $mail->From = $config['username'];
        $mail->FromName = $config['name'];
        $mail->Subject = $title;
        $mail->isHTML($config['html']);
        $mail->msgHTML($content);
        if(is_array($to)) {
            foreach($to as $name=>$email) {
                // 排除数字索引
                if(!is_string($name)) {
                    $name = '';
                }
                $mail->addAddress($email, $name);
            }
        }
        else {
            $name = '';
            $email = $to;
            $mail->addAddress($email, $name);
        }
        // 抄送
        if($cc) {
            if(is_array($cc)) {
                foreach($cc as $name=>$email) {
                    // 排除数字索引
                    if(!is_string($name)) {
                        $name = '';
                    }
                    $mail->addCC($email, $name);
                }
            }
            else {
                $name = '';
                $email = $cc;
                $mail->addCC($email, $name);
            }
        }

        // 对邮件正文进行重新编码，保证中文内容不乱码 如果正文引用该图片 就不会以附件形式存在 而是在正文中
        if(!empty($file)){
            foreach($file as $v) {
                $name = '';
                $path = $v;
                if(is_array($v) && isset($v['path'])) {
                    $path = $v['path'];
                    $name = (isset($v['name']) ? $v['name'] : '');
                }
                $mail->addAttachment($path, $name);
            }
        }

        do {
            // 发送邮件通知
            $boolean = $mail->send();
            $retry--;
        } while($boolean == false && $retry >= 0);
    } catch (Exception $e) {
        log_warning('发送邮件失败: '.$e->getMessage() .'; title = '.$title.'; to = '.var_export($to, true).'; cc = '.var_export($cc, true));
    }
    return $boolean;
}

/**
 * 解析和获取模板内容 用于输出
 * @access public
 * @param string $templateFile 模板文件名
 * @param array $tVar 模板变量
 * @param string $content 模板内容
 * @return string
 */
function fetchView($templateFile='',$tVar=[], $content='') {
    // 模板文件不存在直接返回
    if(!is_file($templateFile)) return NULL;
    // 页面缓存
    ob_start();
    ob_implicit_flush(0);
    // 模板阵列变量分解成为独立变量
    extract($tVar, EXTR_OVERWRITE);
    // 直接载入PHP模板
    empty($content)?include $templateFile:eval('?>'.$content);
    // 获取并清空缓存
    $content = ob_get_clean();
    // 输出模板文件
    return $content;
}

/**
 * Gets individual core information
 *
 * @return array
 */
function getCoreInformation() {
    $cores = [];
    if(is_readable('/proc/stat')) {
        $data = file('/proc/stat');
        foreach( $data as $line ) {
            if( preg_match('/^cpu[0-9]/', $line) )
            {
                $info = explode(' ', $line );
                $cores[] = [
                    'user' => $info[1],
                    'nice' => $info[2],
                    'sys' => $info[3],
                    'idle' => $info[4]
                ];
            }
        }
    }
    return $cores;
}

/**
 * 获取内存信息
 *
 * @param array $filter 获取指定的Key
 *
 * @return array
 */
function getMemoryInformation($filter=[]) {
    $result = [];
    if(is_readable('/proc/meminfo')) {
        $content = file_get_contents('/proc/meminfo');
        $pattern = '/\s*([^:]+):\s*(\d+)\s+(?:KB)?/i';
        if($filter) {
            $pattern = '/\s*('.implode('|', $filter).'):\s*(\d+)\s+(?:KB)?/i';
        }
        $bool = preg_match_all($pattern, $content, $matches);
        if($bool) {
            foreach($matches[1] as $index=>$key) {
                $result[$key] = $matches[2][$index];
            }
        }
    }
    return $result;
}

/**
 * compares two information snapshots and returns the cpu percentage
 *
 * @param $stat1
 * @param $stat2
 *
 * @return array
 */
function getCpuPercentages($stat1, $stat2) {
    if( count($stat1) !== count($stat2) ) {
        return [];
    }
    $cpus = [];
    for( $i = 0, $l = count($stat1); $i < $l; $i++) {
        $dif = [
            'user' => $stat2[$i]['user'] - $stat1[$i]['user'],
            'nice' => $stat2[$i]['nice'] - $stat1[$i]['nice'],
            'sys' => $stat2[$i]['sys'] - $stat1[$i]['sys'],
            'idle' => $stat2[$i]['idle'] - $stat1[$i]['idle']
        ];
        $total = array_sum($dif);
        $cpu = [];
        foreach($dif as $x=>$y) $cpu[$x] = round($y / $total * 100, 1);
        $cpus['cpu' . $i] = $cpu;
    }
    return $cpus;
}
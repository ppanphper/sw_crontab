<?php

namespace Libs;

class Log
{

    protected static $_logPath;
    /**
     * 日志文件名前缀
     * @var
     */
    protected static $_logNamePrefix = '';
    protected static $_dateFmt = 'Y-m-d H:i:s.u';
    /**
     * 日期格式化函数
     * @var string
     */
    protected static $_dateFormatCallable = 'date';
    protected static $_enabled = TRUE;
    /**
     * 当前变量内日志记录的数量
     * @var int
     */
    protected static $_logCount = 0;
    /**
     * 记录日志变量
     * @var array
     */
    protected static $_logs = array();
    /**
     * 记录多少条日志就Flush到存储器
     * @var int
     */
    protected static $_autoFlush = 1000;

    /**
     * 多少秒钟读取一次日志队列长度，有数据就写入缓存区间
     * @var int
     */
    protected static $_statsSleep = 1;

    /**
     * 定时同步数据，fflush将内存中的数据写入到磁盘
     * @var int
     */
    protected static $_timerFFlush = 60;

    /**
     * 记录日志级别
     * @var array
     */
    protected static $_levels = array();

    /**
     * 是否记录所有级别日志
     * @var bool
     */
    protected static $_recordAllLevels = false;

    /**
     * @var LogChannel
     */
    protected static $_logChannel = null;

    protected static $_fp = null;

    protected static $_mode = 0664;

    /**
     * Constructor
     */
    public static function init()
    {
        $config = configItem('log');

        $defaultLogPath = ROOT_PATH . 'Logs' . DIRECTORY_SEPARATOR;

        // 默认日志都写在此目录
        self::$_logPath = !empty($config['path']) ? $config['path'] : $defaultLogPath;
        self::$_logPath = rtrim(self::$_logPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (!empty($config['prefix']) && is_string($config['prefix'])) {
            self::$_logNamePrefix = $config['prefix'];
        }

        if (!empty($config['mode'])) {
            self::$_mode = $config['mode'];
        }

        if (!is_dir(self::$_logPath)) {
            createDir(self::$_logPath);
        }

        if (!is_writable(self::$_logPath)) {
            self::$_enabled = FALSE;
        }

        // 如果配置了log_levels，就使用定义的log_levels
        if (!empty($config['levels'])) {
            // 转换成首字母大写的数组
            self::$_levels = array_map('ucfirst', array_map('strtolower', $config['levels']));
        }

        // 增加PHP核心错误级别
        self::$_levels = array_merge(self::$_levels, array('Core Error', 'Core Warning', 'Compile Error', 'Compile Warning'));

        if (!empty($config['auto_flush'])) {
            self::$_autoFlush = $config['auto_flush'];
        }

        // 多少时间查看一次日志队列，有数据就写入缓存区间，单位秒
        if (!empty($config['stats_sleep'])) {
            self::$_statsSleep = $config['stats_sleep'];
        }

        if ($config['date_format'] != '') {
            self::$_dateFmt = $config['date_format'];
            if (preg_match('#(?<!\\\\)u#', self::$_dateFmt)) {
                self::$_dateFormatCallable = [__CLASS__, 'uDate'];
            }
        }

        // 多长时间后把缓存区间中的内容flush到本地文件
        if (isset($config['timer_fflush'])) {
            self::$_timerFFlush = $config['timer_fflush'];
        }

        // 是否记录所有级别日志
        self::$_recordAllLevels = (array_search('All', self::$_levels) !== FALSE) ? TRUE : FALSE;

        self::$_logChannel = LogChannel::getInstance();

        // 请求完结的时候把日志Flush进文件
        register_shutdown_function([__CLASS__, 'shutdown'], true);
    }


    /**
     * Write Log File
     *
     * Generally this function will be called using the global logMessage() function
     *
     * @param    string $level the error level
     * @param    string $msg the error message
     * @param object | NULL $exceptionObject 抛异常会捕获这个异常对象
     *
     * @return    bool
     */
    public static function writeLog($level = 'error', $msg, $exceptionObject = null)
    {
        if (self::$_enabled == FALSE) {
            return true;
        }

        $level = ucfirst(strtolower($level));
        // 过滤
        if (self::$_recordAllLevels === FALSE && !in_array($level, self::$_levels)) {
            return true;
        }

        if ($exceptionObject instanceof \Exception || $exceptionObject instanceof \Throwable || $level == 'Error') {
            $msg .= PHP_EOL;
            $msg .= 'Error Trace:' . PHP_EOL;
            $msg .= getBacktrace(PHP_EOL, $exceptionObject);
        }

        $time = microtime(true);
        $log = array(
            // 消息
            'm'  => $msg,
            // 日志级别
            'l'  => $level,
            // 消息产生时间
            't'  => $time,
            // 消息存储文件名称
            'fn' => date('Y-m-d', $time)
        );
        return self::$_logChannel->push($log);
    }

    /**
     * 变量内的日志记录写到文件
     * @return bool
     */
    public static function flush()
    {
        if (self::$_enabled == FALSE) {
            return FALSE;
        }

        $logCount = 0;
        $startTime = microtime(true);
        $fileName = self::getFileName();
        if (!is_resource(self::$_fp)) {
            $filePath = self::getLogFilePath();
            // self::$_fp = self::getMmapFileHandle($filePath);
            self::$_fp = fopen($filePath, 'ab');
            if (empty(self::$_fp)) {
                echo self::formatLogMessage(__METHOD__ . ' 打开日志文件失败', 'Core Error');
                return FALSE;
            }
            @chmod($filePath, self::$_mode);
        }

        while (true) {
            if ($logCount >= self::$_autoFlush || ($logCount > 0 && self::$_timerFFlush && (microtime(true) - $startTime) > self::$_timerFFlush)) {
                $boolean = fflush(self::$_fp);
                // 写入成功才把记录数清零
                $boolean && $logCount = 0;
                $startTime = microtime(true);
            }
            $stats = self::$_logChannel->stats();
            if ($stats['queue_num'] > 0) {
                for ($i = 0; $i < $stats['queue_num']; $i++) {
                    $log = self::$_logChannel->pop();
                    if ($log !== false) {
                        $message = self::formatLogMessage($log['m'], $log['l'], $log['t']);
                        // 判断日志是否隔天了
                        if (self::getFileName($log['fn']) !== $fileName) {
                            // 关闭内存映射，底层会自动执行fflush将数据同步到磁盘文件
                            fclose(self::$_fp);
                            // 设置新的日志文件名称，用来判断
                            $fileName = self::getFileName($log['fn']);
                            // 获取新的日志文件路径
                            $filePath = self::getLogFilePath($log['fn']);
                            self::$_fp = fopen($filePath, 'ab');
                            if (empty(self::$_fp)) {
                                echo self::formatLogMessage(__METHOD__ . ' 打开日志文件失败', 'Core Error');
                                return FALSE;
                            }
                            @chmod($filePath, self::$_mode);
                        }

                        fwrite(self::$_fp, $message);
                        $logCount++;
                        continue;
                    }
                }
            }
            sleep(self::$_statsSleep);
        }
    }

    public static function shutdown()
    {
        if (self::$_enabled == FALSE) {
            return FALSE;
        }

        if (!is_resource(self::$_fp)) {
            $filePath = self::getLogFilePath();
            self::$_fp = fopen($filePath, 'ab');
            if (empty(self::$_fp)) {
                echo self::formatLogMessage(__METHOD__ . ' 打开日志文件失败', 'Core Error');
                return FALSE;
            }
            @chmod($filePath, self::$_mode);
        }

        if (is_resource(self::$_fp)) {
            fflush(self::$_fp);
        }

        $fileName = self::getFileName();

        $stats = self::$_logChannel->stats();
        if ($stats['queue_num'] > 0) {
            $logCount = 0;
            for ($i = 0; $i < $stats['queue_num']; $i++) {
                $log = self::$_logChannel->pop();
                if ($log !== false) {
                    $message = self::formatLogMessage($log['m'], $log['l'], $log['t']);
                    // 判断日志是否隔天了
                    if (self::getFileName($log['fn']) !== $fileName) {
                        // 关闭内存映射，底层会自动执行fflush将数据同步到磁盘文件
                        fclose(self::$_fp);
                        // 设置新的日志文件名称，用来判断
                        $fileName = self::getFileName($log['fn']);
                        // 获取新的日志文件路径
                        $filePath = self::getLogFilePath($log['fn']);
                        self::$_fp = fopen($filePath, 'ab');
                        if (empty(self::$_fp)) {
                            echo self::formatLogMessage(__METHOD__ . ' 打开日志文件失败', 'Core Error');
                            return FALSE;
                        }
                        @chmod($filePath, self::$_mode);
                        unset($filePath);
                    }

                    fwrite(self::$_fp, $message);
                    $logCount++;
                }

                if ($logCount >= self::$_autoFlush) {
                    fflush(self::$_fp);
                    $logCount = 0;
                }
            }
            // 关闭内存映射，底层会自动执行fflush将数据同步到磁盘文件
            fclose(self::$_fp);
        }
    }

    /**
     * @return LogChannel
     */
    public static function getChannel()
    {
        return self::$_logChannel;
    }

    /**
     * 格式化日志
     *
     * @param $message
     * @param $level
     * @param $time
     *
     * @return string
     */
    public static function formatLogMessage($message, $level, $time = null)
    {
        $time ?: $time = microtime(true);
        $callback = self::$_dateFormatCallable;
        $date = $callback(...array(self::$_dateFmt, $time));
        return '[' . $date . "] [$level] $message" . PHP_EOL;
    }

    /**
     * 获取日志文件路径
     *
     * @param string $suffix
     * @param string $subdirectory 子目录
     *
     * @return string
     */
    public static function getLogFilePath($suffix = '', $subdirectory = '')
    {
        $logPath = self::$_logPath;
        if ($subdirectory) {
            $logPath .= trim($subdirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        }
        return $logPath . self::getFileName($suffix);
    }

    /**
     * 获取文件名称
     *
     * @param string $suffix
     *
     * @return string
     */
    public static function getFileName($suffix = '')
    {
        $fileName = 'log-' . $suffix . '.log';
        if ($suffix === '') {
            $fileName = 'log-' . date("Y-m-d") . '.log';
        }
        $fileName = self::$_logNamePrefix . $fileName;
        return $fileName;
    }

    /**
     * @param $filePath
     * @param string $openMode
     *
     * @return bool|resource
     */
    public static function getFileHandle($filePath, $openMode = 'a+b')
    {
        $fp = fopen($filePath, $openMode);
        if ($fp) {
            @chmod($filePath, self::$_mode);
        }
        return $fp;
    }

    /**
     * 生成日志文件
     *
     * @param $filePath
     *
     * @return mixed
     */
    public static function generateLogFile($filePath)
    {
        return file_put_contents($filePath, "<" . "?php  if ( ! defined('ROOT_PATH')) exit('No direct script access allowed'); ?" . ">" . PHP_EOL . PHP_EOL);
    }

    /**
     * 支持毫秒格式化日期
     *
     * @param null|float|int $uTimeStamp
     * @param null|string $format
     *
     * @return bool|string
     */
    public static function uDate($format = 'Y-m-d H:i:s.u', $uTimeStamp = null)
    {
        if (is_null($format))
            $format = 'Y-m-d H:i:s.u';
        if (is_null($uTimeStamp))
            $uTimeStamp = microtime(true);

        $timestamp = floor($uTimeStamp);
        $milliseconds = str_pad(round(($uTimeStamp - $timestamp) * 1000), 3, 0);
        // 把格式Y-m-d H:i:s.u的u替换成毫秒值
        return date(preg_replace('#(?<!\\\\)u#', $milliseconds, $format), $uTimeStamp);
    }

    /**
     * @param string $subDirectory
     * @param int $mode
     */
    public static function createLogDir($subDirectory, $mode = 0744)
    {
        $logDir = self::$_logPath . trim($subDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (!is_dir($logDir)) {
            createDir($logDir, $mode);
        }
    }
}
// END Log Class

/* End of file Log.php */
/* Location: ./system/libraries/Log.php */
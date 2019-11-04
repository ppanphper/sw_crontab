<?php
/**
 * 告警模块
 * Created by PhpStorm.
 * User: pandy
 * Date: 2018-04-25
 * Time: 上午10:17
 */

namespace Libs;

use Models\User;
use \Swoole\Client as SwooleClient;

class Report
{
    /**
     * @var LogChannel
     */
    protected static $_logChannel = null;

    /**
     * @var SwooleClient
     */
    protected static $_client = null;


    protected static $_host = '';
    protected static $_port = 9003;
    protected static $_timeout = 0.5;

    protected static $_prefix = 'swc';

    protected static $_enabled = true;

    /** 创建进程失败 */
    const CODE_CREATE_PROCESS_FAILED = 1;

    /** 执行失败 */
    const CODE_EXEC_FAILED = 2;

    protected static $_contentTemplateMaps = [
        self::CODE_CREATE_PROCESS_FAILED => '执行任务[{name}]创建进程失败 taskId: {tid}; runId: {rid}; 时间: [{date}]',
        self::CODE_EXEC_FAILED           => '执行任务[{name}]失败 taskId: {tid}; runId: {rid}; code:{code}; signal:{signal} 时间: [{date}]',
    ];

    public static function init()
    {
        // 初始化日志队列内存表
        self::$_logChannel = new LogChannel(1024 * 1024 * 100);

        $config = configItem('report_monitor');

        if (!empty($config['host'])) {
            self::$_host = $config['host'];
        }

        if (!empty($config['port'])) {
            self::$_host = $config['port'];
        }

        if (!empty($config['prefix'])) {
            self::$_prefix = $config['prefix'];
        }

        if (empty(self::$_host)) {
            self::$_enabled = false;
        }

        /**
         * 由于会写日志，所以此进程需要放在日志进程之前
         */
        register_shutdown_function([__CLASS__, 'shutdown']);
    }

    /**
     * 上报Key到监控系统
     *
     * @param string|array $data
     *
     * 'metric' => '',
     * 'endpoint' => self::$_prefix,
     * 'step' => 60,
     * 'counterType' => 'COUNTER',
     * 'value' => 1,
     * 'tags' => '',
     */
    public static function monitor($data)
    {
        if (self::$_enabled) {
            if (empty(self::$_client)) {
                self::$_client = new SwooleClient(SWOOLE_SOCK_UDP);
            }
            if (!is_array($data)) {
                $data = [
                    'metric' => $data,
                ];
            }
            /**
             * 参考open-falcon
             *
             * metric: 最核心的字段，代表这个采集项具体度量的是什么, 比如是cpu_idle呢，还是memory_free, 还是qps
             * endpoint: 标明Metric的主体(属主)，比如metric是cpu_idle，那么Endpoint就表示这是哪台机器的cpu_idle
             * timestamp: 表示汇报该数据时的unix时间戳，注意是整数，代表的是秒
             * value: 代表该metric在当前时间点的值，float64
             * step: 表示该数据采集项的汇报周期，这对于后续的配置监控策略很重要，必须明确指定。
             * counterType: 只能是COUNTER或者GAUGE二选一，前者表示该数据采集项为计时器类型，后者表示其为原值 (注意大小写)
             *   GAUGE：即用户上传什么样的值，就原封不动的存储
             *   COUNTER：指标在存储和展现的时候，会被计算为speed，即（当前值 - 上次值）/ 时间间隔
             * tags: 一组逗号分割的键值对, 对metric进一步描述和细化, 可以是空字符串. 比如idc=lg，比如service=xbox等，多个tag之间用逗号分割
             */
            $format = [
                'metric'      => '',
                'endpoint'    => self::$_prefix,
                'step'        => 60,
                'counterType' => 'COUNTER',
                'value'       => 1,
                'tags'        => '',
            ];
            $data = array_merge($format, $data);
            $key = json_encode($data, JSON_UNESCAPED_UNICODE);
            self::$_client->sendto(self::$_host, self::$_port, $key);
        }
    }

    /**
     * 创建进程失败报警
     *
     * @param $taskId
     * @param $runId
     *
     * @return bool
     */
    public static function taskCreateProcessFailed($taskId, $runId)
    {
        $data = [
            'taskId' => $taskId,
            'runId'  => $runId,
            'time'   => microtime(true),
            'type'   => self::CODE_CREATE_PROCESS_FAILED,
            'ip'     => SERVER_INTERNAL_IP,
        ];
        return self::$_logChannel->push($data);
    }

    /**
     * 执行失败报警
     *
     * @param integer $taskId
     * @param integer $runId
     * @param integer $code
     * @param integer $signal
     * @param string $msg
     *
     * @return bool
     */
    public static function taskExecFailed($taskId, $runId, $code, $signal, $msg = '')
    {
        // 获取当前第几次重试，假如runId不存在，也会返回0
        $retries = Tasks::$table->get($runId, 'retries');
        $data = [
            'taskId'  => $taskId,
            'runId'   => $runId,
            'time'    => microtime(true),
            'type'    => self::CODE_EXEC_FAILED,
            'code'    => $code,
            'signal'  => $signal,
            'msg'     => $msg,
            'retries' => $retries,
            'ip'      => SERVER_INTERNAL_IP,
            'port'    => SERVER_PORT,
        ];
        return self::$_logChannel->push($data);
    }

    /**
     * 监控报警
     */
    public static function monitorAlarm()
    {
        while (true) {
            self::consumeQueue();
            sleep(1);
        }
    }

    /**
     * 根据用户的信息，进行告警
     *
     * @param $content
     * @param integer $taskId crontab id
     * @param int $noticeWay
     *
     * 0 = 忽略，不通知
     * 1 = 邮件
     * 2 = 短信
     * 3 = 邮件+短信
     * 4 = 微信
     * 5 = 邮件+微信
     * 6 = 短信+微信
     * 7 = 所有
     *
     * @return bool
     *
     * @throws \Exception
     */
    public static function notify($content, $taskId = 0, $noticeWay = Constants::NOTICE_WAY_SEND_MAIL)
    {
        $boolean = false;
        // 所有支持的通知方式
        $noticeWayMaps = Constants::NOTICE_WAY_MAPS;
        $noticeWay = intval($noticeWay);
        if (!isset($noticeWayMaps[$noticeWay])) {
            logWarning(__METHOD__ . ' 通知方式未知: taskId = ' . $taskId . '; noticeWay = ' . var_export($noticeWay, true) . '; content = ' . var_export($content, true));
            return $boolean;
        }
        // 有负责人
        if (!empty($taskId)) {
            try {
                $userData = User::getUserDataByTaskId($taskId);
            } catch (\Exception $e) {
                logError(__METHOD__ . ' 查用户信息失败: taskId = ' . $taskId . '; errorMsg = ' . $e->getMessage());
            }
        }
        // 没有查到负责人信息
        if (empty($userData)) {
            logWarning(__METHOD__ . ' 查不到用户信息: taskId = ' . $taskId . '; 通知系统管理人员');
            $userData = configItem('system_manage_notice_address');
        }
        $to = $cc = [];

        // 发送邮件
        if ($noticeWay & Constants::NOTICE_WAY_SEND_MAIL) {
            $cc = configItem('system_manage_email_address');
        }

        foreach ($userData as $user) {

            // 发送邮件
            if ($noticeWay & Constants::NOTICE_WAY_SEND_MAIL) {
                // 邮箱为空，跳过不处理
                if (!empty($user['email'])) {
                    if (!empty($user['name'])) {
                        $to[$user['name']] = $user['email'];
                    } else {
                        $to[] = $user['email'];
                    }
                }
            }

            // 发送短信
//            if($noticeWay & Constants::NOTICE_WAY_SEND_SMS) {}

            // 发送微信
//            if($noticeWay & Constants::NOTICE_WAY_SEND_WECHAT) {}
        }

        if ($to) {
            $boolean = sendMail(configItem('system_name', 'SWC系统') . '告警', $content, $to, $cc);
        }
        return $boolean;
    }

    /**
     * 消费告警队列
     */
    private static function consumeQueue()
    {
        $dateFormat = configItem('default_date_format', 'Y-m-d H:i:s');
        $stats = self::$_logChannel->stats();
        $loadTasksTable = LoadTasks::getTable();
        if ($stats['queue_num'] > 0) {
            for ($i = 0; $i < $stats['queue_num']; $i++) {
                $data = self::$_logChannel->pop();
                if ($data !== false) {
                    $task = $loadTasksTable->get($data['taskId']);
                    // 任务被删除
                    if (empty($task)) {
                        $task = [
                            'name' => '未知任务',
                        ];
                    }
                    // 通知方式忽略
                    if (isset($task['noticeWay']) && $task['noticeWay'] == Constants::NOTICE_WAY_IGNORE) {
                        continue;
                    }
                    $data['date'] = date($dateFormat, $data['time']);
                    $content = self::formatMessage($task, $data);
                    try {
                        $boolean = self::notify($content, $data["taskId"], $task['noticeWay']);
                    } catch (\Exception $e) {
                        $boolean = false;
                        $content .= ' Error = ' . $e->getMessage();
                    }
                    if (!$boolean) {
                        // 根据返回结果
                        $content = '通知[失败] 内容: ' . $content;
                        logWarning($content);
                    }
                }
            }
        }
    }

    /**
     * @param $task
     * @param $data
     *
     * @return string
     */
    private static function formatMessage($task, $data)
    {
        $template = TPL_PATH . '/Email/simple_table.php';
        $agentName = LoadTasks::getAgentName();
        // 创建进程失败
        if ($data['type'] == self::CODE_CREATE_PROCESS_FAILED) {
            $tVar = [
                'title' => '任务[' . $task["name"] . ']创建进程失败',
                'data'  => [
                    '任务Id'   => $data['taskId'],
                    '运行Id'   => $data['runId'],
                    '执行节点'   => $agentName,
                    '规则'     => $task['rule'],
                    '命令'     => $task['command'],
                    '最大执行时间' => $task['maxTime'],
                    '重试次数'   => $task['retries'],
                    '重试间隔'   => $task['retryInterval'],
                    '时间'     => $data['date'],
                ]
            ];
        } // 执行失败
        else {
            $codeMaps = Constants::CUSTOM_CODE_MAPS;
            // 状态码描述
            $codeDesc = isset($codeMaps[$data['code']]) ? $codeMaps[$data['code']] : $data['code'];
            $tVar = [
                'title' => '任务[' . $task["name"] . ']执行失败',
                'data'  => [
                    '任务Id'   => $data['taskId'],
                    '运行Id'   => $data['runId'],
                    '执行节点'   => $agentName,
                    '规则'     => $task['rule'],
                    '命令'     => $task['command'],
                    '状态码'    => '<strong style="color:red;">' . $codeDesc . '</strong>',
                    '最大执行时间' => $task['maxTime'],
                    '重试次数'   => $task['retries'],
                    '重试间隔'   => $task['retryInterval'],
                    '信号'     => $data['signal'],
                    '时间'     => $data['date'],
                ],
            ];
            if ($data['retries'] > 0) {
                $tVar['data']['当前重试'] = '第' . $data['retries'] . '次';
            }
            if ($data['msg']) {
                $tVar['data']['输出'] = str_replace(["\r\n", "\n"], '<br />', $data['msg']);
            }
        }
        $content = fetchView($template, $tVar);
        return $content;
    }

    /**
     *
     */
    public static function shutdown()
    {
        self::consumeQueue();
    }
}
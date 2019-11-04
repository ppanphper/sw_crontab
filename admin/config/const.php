<?php
namespace app\config;
/**
 * Created by PhpStorm.
 * User: pandy
 * Date: 2018/5/2
 * Time: 下午5:14
 */
class Constants {
    /**
     * 默认节点的端口
     */
    const DEFAULT_AGENT_PORT = 8901;

    const REDIS_KEY_AGENT_SERVER_LIST = 'agent_server_list';

    /**
     * 并发脚本执行标志，多台服务器同时启动相同的脚本，
     * 依靠这个标志来保证单台服务器运行这个脚本，解决脚本单点问题。
     */
    const REDIS_CONCURRENT_FLAG_PREFIX = 'concurrent_flag:';

    // 任务并发数
    const REDIS_KEY_TASK_EXEC_NUM_PREFIX = 'task_exec_num:';
    // 任务执行权
    const REDIS_KEY_TASK_EXEC_RIGHT_PREFIX = 'task_exec_right:';

    /**
     * 数据库中的crontab数据如果有变更，把这个md5值也一起改变。
     * agent发现此值与初始化中的值不一致，就重新加载DB数据到内存表中
     */
    const REDIS_KEY_CRONTAB_CHANGE_MD5 = 'crontab_change_md5';

    /**
     * 存放crontab id hash表，值为更新时间
     * 节点扫描是否有更新，如果存在这个任务，并且上次更新时间小于当前时间，就更新
     */
    const REDIS_KEY_HASH_CRONTAB_CHANGE = 'hash_crontab_change';

    /**
     * 数据库中的agent数据如果有变更，把这个md5值也一起改变
     * agent发现有值，就重新加载DB数据，并删除对应的Key
     * key prefix:IP_PORT
     */
    const REDIS_KEY_AGENT_CHANGE_MD5 = 'agent_change_md5:';

    /**
     * cron规则正则
     *
     * * * * * * * *
     * | | | | | | |
     * | | | | | | +-- Year              (range: 1900-3000)
     * | | | | | +---- Day of the Week   (range: 1-7, 1 standing for Monday)
     * | | | | +------ Month of the Year (range: 1-12)
     * | | | +-------- Day of the Month  (range: 1-31)
     * | | +---------- Hour              (range: 0-23)
     * | +------------ Minute            (range: 0-59)
     * +-------------- Second            (range: 0-59)
     *
     */
    const CRON_RULE_PATTERN = '#^(?:(?:(?:[1-5]?\d(?:-[1-5]?\d)?(?:,[1-5]?\d(?:-[1-5]?\d)?){0,59}|\*)(?:/[1-9]\d?)?)\s+){1,2}' // 秒、分钟
                                .'(?:(?:(?:(?:1?\d|2[0-3])(?:-(?:1?\d|2[0-3]))?)(?:,(?:1?\d|2[0-3])(?:-(?:1?\d|2[0-3]))?){0,23}|\*)(?:/[1-9]\d?)?)\s+' // 小时
                                .'(?:(?:(?:(?:[1-9]|[1-2]\d|3[0-1])(?:-(?:[1-9]|[1-2]\d|3[0-1]))?)(?:,(?:[1-9]|[1-2]\d|3[0-1])(?:-(?:[1-9]|[1-2]\d|3[0-1]))?){0,30}|\*)(?:/[1-9]\d?)?)\s+' // 天
                                .'(?:(?:(?:[1-9]|1[0-2])(?:-(?:[1-9]|1[0-2]))?(?:,(?:[1-9]|1[0-2])(?:-(?:[1-9]|1[0-2]))?){0,11}|\*)(?:/[1-9]\d?)?|(?:jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec))\s+' // 月
                                .'(?:(?:[1-7](?:-[1-7])?(?:,[1-7](?:-[1-7])?){0,6}|\*)(?:/[1-9]\d?)?|(?:mon|tue|wed|thu|fri|sat|sun))$#i'; // 周
    /**
     * 任务命令解析正则
     */
    const CMD_PARSE_PATTERN = '#\s*((?:"[^"]+?"|\'[^\']+?\'|\([^\)]+?\)|`[^`]+?`|[^\s])+)#';

    /**
     * 任务执行状态 0~255
     *
     * 其他自定义状态码,按照处理流程从大到小排序，方便查看日志
     */
    // 命令解析失败
    const CUSTOM_CODE_CMD_PARSE_FAILED = 1001;
    // 任务准备开始
    const CUSTOM_CODE_READY_START = 1000;
    // 创建子进程成功
    const CUSTOM_CODE_CREATE_CHILD_PROCESS_SUCCESS = 999;
    // 创建子进程失败
    const CUSTOM_CODE_CREATE_CHILD_PROCESS_FAILED = 998;
    // 运行用户变更失败
    const CUSTOM_CODE_RUN_USER_CHANGE_FAILED = 997;
    // 并发任务超限
    const CUSTOM_CODE_CONCURRENCY_LIMIT = 996;
    // 子进程开始运行
    const CUSTOM_CODE_CHILD_PROCESS_STARTS_RUN = 995;
    // 执行超时
    const CUSTOM_CODE_EXEC_TIMEOUT = 994;
    // 命令执行失败
    const CUSTOM_CODE_EXEC_FAILED = 126;
    // 错误的命令
    const CUSTOM_CODE_WRONG_COMMAND = 1;
    // 正常结束运行状态码
    const CUSTOM_CODE_END_RUN = 0;
    // 状态码小于255
    const CODE_LESS_THAN_255 = 255;

    // 解析命令失败
    const EXIT_CODE_CMD_PARSE_FAILED = 99;
    // 运行用户变更失败
    const EXIT_CODE_RUN_USER_CHANGE_FAILED = 101;
    // 并发
    const EXIT_CODE_CONCURRENT = 196;

    /**
     * 状态码描述映射
     */
    const CUSTOM_CODE_MAPS = [
        self::CUSTOM_CODE_READY_START => 'Task ready start',
        self::CUSTOM_CODE_CREATE_CHILD_PROCESS_SUCCESS => 'Create child process success',
        self::CUSTOM_CODE_CHILD_PROCESS_STARTS_RUN => 'Child Process starts run',
        self::CUSTOM_CODE_END_RUN => 'End run',
        self::CUSTOM_CODE_RUN_USER_CHANGE_FAILED => 'Run user change failed',
        self::CUSTOM_CODE_CONCURRENCY_LIMIT => 'Concurrency reached threshold',
        self::CUSTOM_CODE_EXEC_TIMEOUT => 'Exec timeout',
        self::CUSTOM_CODE_CREATE_CHILD_PROCESS_FAILED => 'Create child process failed',
        self::CUSTOM_CODE_CMD_PARSE_FAILED => 'CMD parse failed',
        self::CUSTOM_CODE_EXEC_FAILED => 'CMD exec failed',
        self::CUSTOM_CODE_WRONG_COMMAND => 'Wrong command',
        self::CODE_LESS_THAN_255 => 'Code less than 255',
    ];

    // 忽略，不通知
    const NOTICE_WAY_IGNORE = 0;
    // 发送邮件
    const NOTICE_WAY_SEND_MAIL = 1;
    // 发送短信
    const NOTICE_WAY_SEND_SMS = 2;
    // 发送邮件+短信
    const NOTICE_WAY_SEND_MAIL_SMS = 3;
    // 发送微信
    const NOTICE_WAY_SEND_WECHAT = 4;
    // 发送邮件+微信
    const NOTICE_WAY_SEND_MAIL_WECHAT = 5;
    // 发送短信+微信
    const NOTICE_WAY_SEND_SMS_WECHAT = 6;
    // 所有通知方式
    const NOTICE_WAY_SEND_ALL = 7;

    const NOTICE_WAY_MAPS = [
        self::NOTICE_WAY_IGNORE => 'Ignore',
        self::NOTICE_WAY_SEND_MAIL => 'Email',
        self::NOTICE_WAY_SEND_SMS => 'Sms',
        self::NOTICE_WAY_SEND_MAIL_SMS => 'Email and Sms',
        self::NOTICE_WAY_SEND_WECHAT => 'WeChat',
        self::NOTICE_WAY_SEND_MAIL_WECHAT => 'Email and WeChat',
        self::NOTICE_WAY_SEND_SMS_WECHAT => 'Sms and WeChat',
        self::NOTICE_WAY_SEND_ALL => 'All way',
    ];

    /**======================================= 与Agent通信相关常量 ========================================*/
    // cmd for the server control
    const SW_CONTROL_CMD = 'SC';
    // cmd for the api
    const SW_API_CMD = 'SA';

    //是否开启数据签名，服务端客户端都需要打开，打开后可以强化安全，但会降低一点性能
    const SW_DATASIGEN_FLAG = true;
    //上面开关开启后，用于加密串混淆结果，请保持客户端和服务端一致
    const SW_DATASIGEN_SALT = "20^8EyFbLo@7mpj960ME";

    // Success work
    const STATUS_CODE_SUCCESS = 0;
    // async task success
    const STATUS_CODE_ASYNC_TASK_SUCCESS = 100001;
    // unknow task type
    const STATUS_CODE_UNKNOW_TASK_TYPE = 100002;
    // you must fill the cmd parameter on you request
    const STATUS_CODE_MISS_CMD_PARAM = 100003;
    // there is no config for this group
    const STATUS_CODE_NO_CONFIG_FOR_THIS_GROUP = 100004;
    // Signature error
    const STATUS_CODE_SIGNATURE_ERROR = 100005;
    // Pack encode type wrong
    const STATUS_CODE_PACKET_ENCODE_TYPE_WRONG = 100006;
    // socket error the recive packet length is wrong
    const STATUS_CODE_RECEIVE_PACKET_LENGTH_WRONG = 100007;
    // the return guid wrong may be the socket trasfer wrong data
    const STATUS_CODE_GUID_WRONG = 100008;
    // the recive wrong or timeout
    const STATUS_CODE_RECEIVE_WRONG_OR_TIMEOUT = 100009;
    // there is no server can connect
    const STATUS_CODE_NO_SERVER_CAN_CONNECT = 100010;
    // unknow cmd of controlle
    const STATUS_CODE_UNKNOW_CMD = 100011;
    // get Async Result Fail: Client Closed.
    const STATUS_CODE_GET_ASYNC_RESULT_FAIL = 100012;
    // unknow communicate mode have been set
    const STATUS_CODE_UNKNOW_COMMUNICATE_MODE = 100099;
    // guid wront please retry..
    const STATUS_CODE_GUID_WRONG_PLEASE_RETRY = 100100;
    // if server no return , print "json" content but response header "Content-Type" is text/html
    const STATUS_CODE_PRINT_JSON_TEXT_HTML = 100995;
    // if server no return ,print "json" content
    const STATUS_CODE_PRINT_JSON = 100996;
    // if server no return , print "js redirect" content
    const STATUS_CODE_JS_REDIRECT = 100997;
    // if server no return , print "redirect" content
    const STATUS_CODE_REDIRECT = 100998;
    // exception exit. see "rpc_exit" function
    const STATUS_CODE_EXCEPTION_EXIT = 100999;
    /**======================================= 与Agent通信相关常量 End ========================================*/
}
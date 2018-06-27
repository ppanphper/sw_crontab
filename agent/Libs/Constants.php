<?php
/**
 * Created by PhpStorm.
 * User: pandy
 * Date: 2018/4/24
 * Time: 下午3:13
 */

namespace Libs;

class Constants
{
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
     * 删除的crontab需要在内存表中删除
     */
    const REDIS_KEY_CRONTAB_DELETE_HASH = 'crontab_delete_hash';

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
    . '(?:(?:(?:(?:1?\d|2[0-3])(?:-(?:1?\d|2[0-3]))?)(?:,(?:1?\d|2[0-3])(?:-(?:1?\d|2[0-3]))?){0,23}|\*)(?:/[1-9]\d?)?)\s+' // 小时
    . '(?:(?:(?:(?:[1-9]|[1-2]\d|3[0-1])(?:-(?:[1-9]|[1-2]\d|3[0-1]))?)(?:,(?:[1-9]|[1-2]\d|3[0-1])(?:-(?:[1-9]|[1-2]\d|3[0-1]))?){0,30}|\*)(?:/[1-9]\d?)?)\s+' // 天
    . '(?:(?:(?:[1-9]|1[0-2])(?:-(?:[1-9]|1[0-2]))?(?:,(?:[1-9]|1[0-2])(?:-(?:[1-9]|1[0-2]))?){0,11}|\*)(?:/[1-9]\d?)?|(?:jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec))\s+' // 月
    . '(?:(?:[1-7](?:-[1-7])?(?:,[1-7](?:-[1-7])?){0,6}|\*)(?:/[1-9]\d?)?|(?:mon|tue|wed|thu|fri|sat|sun))$#i'; // 周
    /**
     * 任务命令解析正则
     */
    const CMD_PARSE_PATTERN = '#\s*((?:"[^"]+?"|\'[^\']+?\'|[^\s])+)#';

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
        self::CUSTOM_CODE_READY_START                  => '任务准备开始',
        self::CUSTOM_CODE_CREATE_CHILD_PROCESS_SUCCESS => '创建子进程成功',
        self::CUSTOM_CODE_CHILD_PROCESS_STARTS_RUN     => '子进程开始运行',
        self::CUSTOM_CODE_END_RUN                      => '正常结束运行',
        self::CUSTOM_CODE_RUN_USER_CHANGE_FAILED       => '运行用户变更失败',
        self::CUSTOM_CODE_CONCURRENCY_LIMIT            => '并发达到阀值',
        self::CUSTOM_CODE_EXEC_TIMEOUT                 => '执行超时',
        self::CUSTOM_CODE_CREATE_CHILD_PROCESS_FAILED  => '创建子进程失败',
        self::CUSTOM_CODE_CMD_PARSE_FAILED             => '命令解析失败',
        self::CUSTOM_CODE_EXEC_FAILED                  => '命令执行失败',
        self::CUSTOM_CODE_WRONG_COMMAND                => '错误的命令',
        self::CODE_LESS_THAN_255                       => '状态码小于255',
    ];

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
        self::NOTICE_WAY_SEND_MAIL        => 'Email',
        self::NOTICE_WAY_SEND_SMS         => 'Sms',
        self::NOTICE_WAY_SEND_MAIL_SMS    => 'Email and Sms',
        self::NOTICE_WAY_SEND_WECHAT      => 'WeChat',
        self::NOTICE_WAY_SEND_MAIL_WECHAT => 'Email and WeChat',
        self::NOTICE_WAY_SEND_SMS_WECHAT  => 'Sms and WeChat',
        self::NOTICE_WAY_SEND_ALL         => 'All way',
    ];

    const MONITOR_KEY_CMD_PARSE_FAILED = 'cmd_parse_failed';
    const MONITOR_KEY_CREATE_PROCESS_SUCCESS = 'create_process.success';
    const MONITOR_KEY_CREATE_PROCESS_FAILED = 'create_process.failed';
    const MONITOR_KEY_RUN_USER_CHANGE_FAILED = 'run_user_change.failed';
    const MONITOR_KEY_CHILD_PROCESS_STARTS_RUN = 'child_process_starts_run';
    const MONITOR_KEY_CONCURRENCY_LIMIT = 'concurrency_limit';
    const MONITOR_KEY_EXEC_TIMEOUT = 'exec_timeout';
    const MONITOR_KEY_EXEC_SUCCESS = 'exec_success';
    const MONITOR_KEY_EXEC_FAILED = 'exec_failed';
}
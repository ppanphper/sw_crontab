<?php
/**
 * Created by PhpStorm.
 * User: pandy
 * Date: 2018/4/25
 * Time: 上午10:59
 */
return [
    // 系统名称(发送告警时使用)
    'system_name'                  => 'SWC系统',
    // 默认选择DB
    'default_select_db'            => 'sw_crontab',
    // 默认日期格式
    'default_date_format'          => 'Y-m-d H:i:s',
    // 系统默认通知地址列表，如果任务负责人无法查到，就通知系统负责人
    'system_manage_notice_address' => [
        [
            'name'  => 'SWC系统管理员',
            'email' => 'admin@Email.com',
            // 暂未实现
//            'mobile' => 'xxxxxxxxxx',
            // 暂未实现
//            'wechat' => 'xxxxxxxxxxx',
        ],
//        [
//            // 暂未实现
//            'mobile' => 'xxxxxxxxxx',
//            // 暂未实现
//            'wechat' => 'xxxxxxxxxxx',
//        ]
    ],

    // 报警邮件抄送地址
    'system_manage_email_address'  => [
        'SWC系统管理员' => 'admin@Email.com',
    ],


    // swoole安装版本必须是这个版本或以上
    'swoole_version'               => '1.10.3',

    'server_listen_host'         => '0.0.0.0',
    /**
     * swoole-1.9.6增加了随机监听可用端口的支持，$port参数可以设置为0，操作系统会随机分配一个可用的端口，进行监听。
     * 可以通过读取$server->port得到分配到的端口号。
     */
    'server_listen_port'         => 8901,

    // 最多载入任务数量，"本节点可处理任务内存"表最大行数
    'task_max_load_size'         => 8192,
    // 最大进程数，"任务进程内存"表最大行数
    'process_max_size'           => 1024,
    // 同时运行任务最大数量, "任务内存"表最大行数
    'task_max_concurrent_size'   => 1024,

    // 10分钟重新加载一次任务到内存表
    'task_reload_interval'       => 600,
    // 监听数据库是否有变更的频率(单位秒)，如果变更，就重新加载任务到内存表
    'monitor_reload_interval'    => 10,

    // 写入db日志最大重试次数
    'flush_db_log_max_retry_num' => 3,

    'log'            => [
        'path'         => '',
        // 日志文件名前缀
        'prefix'       => '',
        // 记录日志级别
        'levels'       => ['Error', 'Warning', 'Info'],
        // 达到多少条日志flush到本地文件
        'auto_flush'   => 1000,
        // 多少秒钟读取一次日志队列长度，有数据就写入缓存区间
        'stats_sleep'  => 5,
        // 达到多少秒钟就把缓存区间中的日志flush到本地文件
        'timer_fflush' => 300,
        // 时间格式化
        'date_format'  => 'Y-m-d H:i:s.u',
        // 日志文件权限
        'mode'         => 0644,
    ],

    /**
     * 上报监控系统
     */
    'report_monitor' => [
        'host' => 'localhost',
        'port' => 9003,
    ]
];
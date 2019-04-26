<?php
/**
 * Created by PhpStorm.
 * User: pandy
 * Date: 2018/5/22
 * Time: 下午6:33
 */
return [
    // 管理员邮箱地址
    'adminEmail'      => [
		'admin@Email.com' => '管理员'
	],
    // 监控频率, 60秒
    'monitorInterval' => 60,
    // 超过600秒没有上报, 就报警
    'offlineTime'     => 600,
    // 可选择的运行时用户
    'runUserItems' => [
        'work' => 'work',
        'root' => 'root',
		’www' => 'www',
		'nobody' => 'nobody',
    ]
];
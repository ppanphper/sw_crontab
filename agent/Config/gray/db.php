<?php
/**
 * Created by PhpStorm.
 * User: pandy
 * Date: 2018/4/24
 * Time: 上午11:46
 */
return [
    'sw_crontab' => [
        'dsn'            => 'mysql:host=127.0.01;port=3306;dbname=sw_crontab',
        'username'       => 'root',
        'password'       => '',
        /**
         * PDO::ATTR_EMULATE_PREPARES 启用或禁用预处理语句的模拟。
         * 有些驱动不支持或有限度地支持本地预处理。使用此设置强制PDO总是模拟预处理语句（如果为 TRUE ），或试着使用本地预处理语句（如果为 FALSE）。
         * 如果驱动不能成功预处理当前查询，它将总是回到模拟预处理语句上。
         */
        'emulatePrepare' => true,
    ]
];
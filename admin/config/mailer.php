<?php
/**
 * Created by PhpStorm.
 * User: pandy
 * Date: 2018/5/28
 * Time: 上午10:43
 */
return [
    'class'            => 'yii\swiftmailer\Mailer',
    // send all mails to a file by default. You have to set
    // 'useFileTransport' to false and configure a transport
    // for the mailer to send real emails.
    'useFileTransport' => false,
    'transport'        => [
        'class'      => 'Swift_SmtpTransport',
        'host'       => 'smtp.exmail.qq.com',
        'username'   => 'admin@Email.com', // 改成你自己的账号
        'password'   => '',// 改成你自己的密码
        'port'       => '465',
        'encryption' => 'ssl',
    ],
    'messageConfig'    => [
        'charset' => 'UTF-8',
        'from'    => [
            'admin@Email.com' => 'SWC' // 改成你自己的昵称
        ]
    ],
];
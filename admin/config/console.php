<?php
require_once __DIR__ . '/const.php';

$pathArray = [
    __DIR__ . '/' . YII_ENV,
    __DIR__,
];
$keyArray = [
    'params',
    'db',
    'redis',
    'mailer',
];
foreach ($keyArray as $key) {
    foreach ($pathArray as $path) {
        $filePath = "{$path}/{$key}.php";
        if (file_exists($filePath)) {
            ${$key} = require $filePath;
            break;
        }
    }
}

$config = [
    'id'                  => 'SWC',
    'basePath'            => dirname(__DIR__),
    'bootstrap'           => ['log'],
    'controllerNamespace' => 'app\commands',
    // 设置目标语言为简体中文
    'language'       => 'zh-CN',
    // 设置源语言为英语
    'sourceLanguage' => 'en-US',
    'components'          => [
        'cache'     => [
            'class' => 'yii\caching\FileCache',
        ],
        'log'       => [
            'targets' => [
                [
                    'class'  => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning', 'info'],
                ],
            ],
        ],
        'db'        => $db,
        'redis'     => $redis,
        'formatter' => [
            'dateFormat'        => 'yyyy-MM-dd',
            'datetimeFormat'    => 'yyyy-MM-dd HH:mm:ss',
            'decimalSeparator'  => ',',
            'thousandSeparator' => ' ',
            'currencyCode'      => 'CNY',
        ],
        'i18n'         => [
            'translations' => [
                'app*' => [
                    'class'    => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@app/messages',
                    // 'sourceLanguage' => 'en-US',
                    'fileMap'  => [
                        'app'       => 'app.php',
                        'app/error' => 'error.php',
                    ],
                ],
            ],
        ],
        'mailer'    => $mailer,
    ],
    'params'              => $params,
    /*
    'controllerMap' => [
        'fixture' => [ // Fixture generation command line.
            'class' => 'yii\faker\FixtureController',
        ],
    ],
    */
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
    ];
}

return $config;

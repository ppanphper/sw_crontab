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
    'id'             => 'SWC',
    'name'           => 'SWC系统',
    'basePath'       => dirname(__DIR__),
    'bootstrap'      => ['log'],
    // 设置目标语言为简体中文
    'language'       => 'zh-CN',
    // 设置源语言为英语
    'sourceLanguage' => 'en-US',
    'aliases'        => [
        '@bower'     => '@vendor/bower-asset',
        '@npm'       => '@vendor/npm-asset',
        "@mdm/admin" => "@vendor/mdmsoft/yii2-admin",
    ],
    'components'     => [
        'request'      => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'JHCvgkRhspWJGFiqoWROC6HLapVYSy5D',
        ],
        'cache'        => [
            'class'     => 'ppanphper\redis\Cache',
            'keyPrefix' => 'PHP:',
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer'       => $mailer,
        'log'          => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets'    => [
                [
                    'class'  => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db'           => $db,
        'urlManager'   => [
            //用于表明urlManager是否启用URL美化功能，在Yii1.1中称为path格式URL，
            // Yii2.0中改称美化。
            // 默认不启用。但实际使用中，特别是产品环境，一般都会启用。
            'enablePrettyUrl'     => true,
            // 是否启用严格解析，如启用严格解析，要求当前请求应至少匹配1个路由规则，
            // 否则认为是无效路由。
            // 这个选项仅在 enablePrettyUrl 启用后才有效。
            'enableStrictParsing' => false,
            // 是否在URL中显示入口脚本。是对美化功能的进一步补充。
            'showScriptName'      => false,
            // 指定续接在URL后面的一个后缀，如 .html 之类的。仅在 enablePrettyUrl 启用时有效。
            'suffix'              => '',
            'rules'               => [
                ''                              => 'index/index',
                "<controller:\w+>/<id:\d+>"     => "<controller>/view",
                "<controller:\w+>/<action:\w+>" => "<controller>/<action>"
            ],
        ],
        //components数组中加入authManager组件,有PhpManager和DbManager两种方式,
        //PhpManager将权限关系保存在文件里,这里使用的是DbManager方式,将权限关系保存在数据库.
        "authManager"  => [
            "class"        => 'yii\rbac\DbManager', //这里记得用单引号而不是双引号
            "defaultRoles" => ["guest"],
        ],
        'user'         => [
            'identityClass'   => 'app\models\User',
            'enableAutoLogin' => true,
        ],
        'redis'        => $redis,
        'session'      => [
            'class'     => 'ppanphper\redis\Session',
            'keyPrefix' => 'SESS:',
            // 解决与主域下的PHPSESSID相同导致的无法获取正确的SESSION
            'name'      => 'SWCSESSID',
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
        'formatter'    => [
            'dateFormat'        => 'yyyy-MM-dd',
            'datetimeFormat'    => 'yyyy-MM-dd HH:mm:ss',
            'decimalSeparator'  => ',',
            'thousandSeparator' => ' ',
            'currencyCode'      => 'CNY',
        ],
        // 资源包依赖覆盖
        'assetManager' => [
            'appendTimestamp' => true,
            'bundles'         => [
                'yii\web\YiiAsset'    => [
                    'depends' => [
                        'yii\web\JqueryAsset',
                        'app\assets\JqueryUIAsset',
                    ]
                ],
                'app\assets\AppAsset' => [
                    'depends' => [
                        'dmstr\web\AdminLteAsset',
                        'dosamigos\selectize\SelectizeAsset',
                        'hiqdev\assets\icheck\iCheckAsset'
                    ]
                ],
            ],
        ],
    ],
    "modules"        => [
        "admin" => [
            "class" => "mdm\admin\Module",
        ],
    ],
    'as access'      => [
        'class'        => 'app\components\AccessControl',
        'allowActions' => [
            //这里是允许访问的action
            //controller/action
            'site/*',
        ]
    ],
    'as loginAccess' => [
        'class'  => 'yii\filters\AccessControl',
        'except' => ['site/login'],
        'rules'  => [
            [
                'allow' => true,
                'roles' => ['@'],
            ],
        ],
    ],
    /** End */
    'params'         => $params,
];
if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}
return $config;

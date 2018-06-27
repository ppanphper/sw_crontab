<?php

// change the following paths if necessary
if (file_exists(dirname(__FILE__).'/env.php')){
	$envInit = include_once dirname(__FILE__).'/env.php';//载入环境变量
}
// comment out the following two lines when deployed to production
defined('YII_ENV') or define('YII_ENV', !empty($envInit['env']) ? $envInit['env'] : 'prod');
defined('YII_DEBUG') or define('YII_DEBUG', (YII_ENV == 'dev'));

require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');

$config = require(__DIR__ . '/../config/web.php');

(new yii\web\Application($config))->run();

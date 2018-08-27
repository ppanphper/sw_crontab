<?php
/**
 * Created by PhpStorm.
 * User: pandy
 * Date: 2018/5/23
 * Time: 上午10:13
 */
return [
    'class'             => 'ppanphper\redis\Connection',

    'hostname' => '127.0.0.1',
    'password' => null,
    'port' => 6379,

    /**
     * 集群模式
     */
    'servers'           => [
        '192.168.19.22:6379',
        '192.168.19.22:6380',
        '192.168.19.21:6379', // master
        '192.168.19.21:6380', // master
        '192.168.19.20:6379', // master
        '192.168.19.20:6380',
    ],
    'prefix'            => 'PHP:',
    'database'          => 0,
    'connectionTimeout' => 1,
    'dataTimeout'       => 0.5,

    /**
     * client_type 参数值范围:
     * @see CI_Cache_redis::CLIENT_TYPE_PHP_REDIS = 1
     * @see CI_Cache_redis::CLIENT_TYPE_PHP_REDIS_CLUSTER = 2
     */
    'clientType'        => 1,
];
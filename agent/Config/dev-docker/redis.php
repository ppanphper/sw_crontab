<?php
return [
    'socket_type' => 'tcp',
    'prefix'      => 'SWC:',
    'host'        => 'redis',
    'password'    => null,
    'port'        => 6379,
    'timeout'     => 1,

    /**
     * 集群模式
     */
    'phpredis_cluster' => [
        'prefix'       => 'SWC:',
        'servers'      => [
            '127.0.0.1:6379',
            '127.0.0.1:6380',
        ],
        'timeout'      => 1,
        'read_timeout' => 1,
        'persistent'   => false,
    ],

    /**
     * client_type 参数值范围:
     * @see RedisClient::CLIENT_TYPE_PHP_REDIS = 1
     * @see RedisClient::CLIENT_TYPE_PHP_REDIS_CLUSTER = 2
     * @see RedisClient::CLIENT_TYPE_PREDIS = 3
     */
    'client_type'      => 1,
];
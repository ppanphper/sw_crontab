<?php
return [
    'socket_type'      => 'tcp',
    'host'             => '127.0.0.1',
    'password'         => NULL,
    'port'             => 6379,
    // 是否使用长连接
    'pconnect'         => true,
    'timeout'          => 0.5,
    // key前缀
    'prefix'           => 'SWC:',

    /**
     * 集群模式
     */
    'phpredis_cluster' => [
        'prefix'       => 'PHP:',
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
     * @see CI_Cache_redis::CLIENT_TYPE_PHP_REDIS = 1
     * @see CI_Cache_redis::CLIENT_TYPE_PHP_REDIS_CLUSTER = 2
     * @see CI_Cache_redis::CLIENT_TYPE_PREDIS = 3
     */
    'client_type'      => 2,
];

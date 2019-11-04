<?php

namespace Libs;

use \Exception;
use Redis;
use RedisCluster;
use RedisException;

class RedisClient
{
    // phpRedis客户端
    const CLIENT_TYPE_PHP_REDIS = 1;
    // phpRedis cluster 客户端
    const CLIENT_TYPE_PHP_REDIS_CLUSTER = 2;
    // predis客户端
    const CLIENT_TYPE_PREDIS = 3;

    /**
     * 获取Redis客户端对象方法映射
     * @var array
     */
    protected $_clientTypeObjectMap = [
        self::CLIENT_TYPE_PHP_REDIS         => 'getRedisObject',
        self::CLIENT_TYPE_PHP_REDIS_CLUSTER => 'getRedisClusterObject',
        self::CLIENT_TYPE_PREDIS            => 'getPredisObject',
    ];
    /**
     * Default config
     *
     * @static
     * @var    array
     */
    protected static $_defaultConfig = array(
        'socket_type'      => 'tcp',
        'host'             => '127.0.0.1',
        'password'         => NULL,
        'port'             => 6379,
        // 是否使用长连接
        'pconnect'         => true,
        'timeout'          => 0.5,
        // key前缀
        'prefix'           => '',
        // 优先使用哪种客户端连接Redis
        'client_type'      => self::CLIENT_TYPE_PHP_REDIS,
        /**
         * 是否使用igbinary扩展来序列化
         * 注意 : 由于项目之前使用的是php serialize函数进行数据序列化，如果改用igbinary会出现不兼容，导致程序异常.
         * 建议 : 新项目上线时，并且Redis之间的数据不依赖的话，可以使用该扩展进行序列化
         */
        'use_igbinary'     => false,
        // 是否使用phpredis cluster
        'phpredis_cluster' => [],
        // 是否使用Predis
        'predis'           => [],
    );

    protected static $_config = array();

    /**
     * Redis connection
     *
     * @var object
     */
    protected $_redis = null;

    /**
     * 是否禁用Redis, 假如用户没有Redis配置文件
     * @var bool
     */
    protected $_disabled = FALSE;

    /**
     * 连接方法. 默认短连接
     * @var string
     */
    protected $_connect = 'connect';

    /**
     * Redis集群的默认配置, 需要useCluster为TURE
     * @var array
     * profile: specifies the profile to use to match a specific version of Redis.
     * prefix: prefix string automatically applied to keys found in commands.
     * exceptions: whether the client should throw or return responses upon Redis errors.
     * connections: list of connection backends or a connection factory instance.
     * cluster: specifies a cluster backend (predis, redis or callable object).
     * replication: specifies a replication backend (TRUE, sentinel or callable object).
     * aggregate: overrides cluster and replication to provide a custom connections aggregator.
     * parameters: list of default connection parameters for aggregate connections.
     */
    protected $_pRedisOptions = array(
        // 前缀
        'prefix'  => '',
        'cluster' => 'redis'
    );

    /**
     * PRedis配置
     * @var array
     */
    protected $_pRedisConfig = array();

    /**
     * PHPRedis集群配置
     * @var array
     */
    protected $_phpRedisConfig = [
        'prefix'       => '', // key前缀
        'servers'      => [], // 节点
        'timeout'      => 1, // 连接超时时间
        'read_timeout' => 0.5, // 读取/写入超时时间
        'persistent'   => true, // 长连接
    ];

    /**
     * 是否手动序列化
     * @var bool
     */
    protected $_manualSerialization = FALSE;

    /**
     * 脚本列表
     * @var array
     */
    protected $_funcTable = [
        // 增量计数器，第一次增量计数的时候，给key加上过期时间，解决并发问题 eg: evalScript('incr', 'key', expireTime)
        'incr'       => [
            'sha1'   => '727c0136efce8e1e7b34a5d1a29c87b77a9348ff',
            'script' => "local count = redis.call('incr',KEYS[1]); if tonumber(count) == 1 then redis.call('expire',KEYS[1],ARGV[1]); end; return count;"
        ],
        // 增量计数器，并在增量值超过最大值时，重置为0 eg: evalScript('incr_reset', 'key', [maxCounter，expireTime])
        'incr_reset' => [
            'sha1'   => '064e70749675e1c315270a18e5c38ae3f314498a',
            'script' => "local count = redis.call('incr',KEYS[1]); if tonumber(count) == 1 then redis.call('expire',KEYS[1],ARGV[2]); end; if tonumber(count) > tonumber(ARGV[1]) then redis.call('set', KEYS[1], 0); return 0; end; return count;",
        ],
        // 增量计数器，如果当前值没有大于限定值，才可以加一并返回[1, 累加后的值]，否则返回[0, 当前值] eg: evalScript('incr_max', 'key', [maxCounter, expireTime])
        'incr_max'   => [
            'sha1'   => '56a52dbab84bd9b0fc0a8330caff45c31d2df9ab',
            'script' => "local count = redis.call('get',KEYS[1]); if ( count == false or tonumber(count) < tonumber(ARGV[1]) ) then count = redis.call('incr', KEYS[1]); if count == 1 then redis.call('expire',KEYS[1],ARGV[2]); end; return {1, count}; else return {0, count}; end;",
        ],
        // 存在才将 key 中储存的数字值减一 eg: evalScript('decr_exist', 'key')
        'decr_exist' => [
            'sha1'   => 'b8fdb9f741719829325bcc7253b93eed7b526ccb',
            'script' => "local count = redis.call('exists',KEYS[1]); if tonumber(count) == 1 then count = redis.call('decr',KEYS[1]); end; return count;"
        ],
    ];

    /**
     * 是否使用的是PHPRedis扩展
     * @var bool
     */
    protected $_isPhpRedis = true;

    // ------------------------------------------------------------------------

    /**
     * Class constructor
     *
     * @param array $config
     *
     * @throws Exception
     */
    public function __construct($config)
    {
        // 加载配置
        if (!empty($config)) {
            self::$_config = array_merge(self::$_defaultConfig, $config);
        }

        // 没有配置就禁用Redis
        if (empty(self::$_config)) {
            $this->_disabled = true;
            return;
        }

        // 客户端类型是否支持
        if (!isset(self::$_config['client_type']) || !in_array(self::$_config['client_type'], $this->getAllClientType(), true)) {
            throw new Exception('Invalid client_type');
        }

        // 如果是PHPRedis 或者 PHPRedis集群
        $this->_isPhpRedis = in_array(self::$_config['client_type'], [self::CLIENT_TYPE_PHP_REDIS, self::CLIENT_TYPE_PHP_REDIS_CLUSTER], true);
        if ($this->_isPhpRedis) {
            // 是否支持Redis
            if (!$this->is_supported()) {
                throw new Exception("Redis class is not exists,Please make sure is installed!");
            }
        }

        switch (self::$_config['client_type']) {
            // PHPRedis
            case self::CLIENT_TYPE_PHP_REDIS:
                $this->_connect = (isset(self::$_config['pconnect']) && self::$_config['pconnect']) ? 'pconnect' : 'connect';
                break;
            // PHPRedis Cluster
            case self::CLIENT_TYPE_PHP_REDIS_CLUSTER:
                $this->_phpRedisConfig = isset(self::$_config['phpredis_cluster']) ? self::$_config['phpredis_cluster'] : [];
                // 是否提供Redis节点
                if (!is_array($this->_phpRedisConfig['servers']) || count($this->_phpRedisConfig['servers']) < 1) {
                    throw new Exception('Using the redis cluster, but the servers parameter is empty.');
                }
                break;
            // Predis
            case self::CLIENT_TYPE_PREDIS:
                $this->_pRedisConfig = isset(self::$_config['predis']) ? self::$_config['predis'] : [];
                // 是否提供Redis节点
                if (!is_array($this->_pRedisConfig['servers']) || count($this->_pRedisConfig['servers']) < 1) {
                    throw new Exception('Using the redis cluster, but the servers parameter is empty.');
                }
                // PRedis的配置选项
                if (is_array($this->_pRedisConfig['options']) && !empty($this->_pRedisConfig['options'])) {
                    $this->_pRedisOptions = array_merge($this->_pRedisOptions, $this->_pRedisConfig['options']);
                }
                break;
        }
    }

    /**
     * 获取Redis实例
     *
     * @return RedisClient
     */
    public static function getInstance()
    {
        static $redisObject = null;
        if ($redisObject === null) {
            // 加载Redis配置文件
            $redisConfig = configItem(null, null, 'redis');

            // 初始化Redis客户端
            $redisObject = new self($redisConfig);
        }
        return $redisObject;
    }

    public function getHandle()
    {
        // 如果没有配置Redis，就不使用Redis
        if ($this->_disabled) {
            return null;
        }

        if ($this->_redis == null) {
            try {
                $this->_redis = $this->{$this->_clientTypeObjectMap[self::$_config['client_type']]}();
            } catch (Exception $e) {
                $this->_redis = null;
            }
        }
        return $this->_redis;
    }

    /**
     * 获取已经与Redis建立连接的对象
     * @return Redis
     * @throws Exception
     * @throws RedisException
     */
    private function getRedisObject()
    {
        $object = 'Redis';
//		if(version_compare(SWOOLE_VERSION, '2.0.0', '>=')) {
//			$object = 'Swoole\Coroutine\Redis';
//		}
        $redis = new $object();
        // tcp socket
        if (self::$_config['socket_type'] === 'unix') {
            $success = $redis->{$this->_connect}(self::$_config['socket']);
        } else {
            $success = $redis->{$this->_connect}(self::$_config['host'], self::$_config['port'], self::$_config['timeout']);
        }
        // 是否连接成功
        if ($success) {
            // 连接成功但校验密码失败
            if (!empty(self::$_config['password']) && !$redis->auth(self::$_config['password'])) {
                // 密码验证不通过，那么就不再重连尝试了
                throw new RedisException('Cache: Redis authentication failed.');
            }
        } else {
            throw new RedisException('Cache: Redis connection failed. Check your configuration.');
        }
        $redis->setOption(Redis::OPT_PREFIX, self::$_config['prefix']);
        $redis->setOption(Redis::OPT_SERIALIZER, $this->getSerializerType());
        return $redis;
    }

    /**
     * 获取PHP Redis Cluster
     * @return RedisCluster
     */
    private function getRedisClusterObject()
    {
        $redisCluster = new RedisCluster(null, $this->_phpRedisConfig['servers'], $this->_phpRedisConfig['timeout'], $this->_phpRedisConfig['read_timeout'], $this->_phpRedisConfig['persistent']);
        // In the event we can't reach a master, and it has slaves, failover for read commands
        $redisCluster->setOption(RedisCluster::OPT_SLAVE_FAILOVER, RedisCluster::FAILOVER_ERROR);
        $redisCluster->setOption(RedisCluster::OPT_PREFIX, $this->_phpRedisConfig['prefix']);
        $redisCluster->setOption(RedisCluster::OPT_SERIALIZER, $this->getSerializerType());
        return $redisCluster;
    }

    /**
     * 获取Predis对象
     * @return \Predis\Client
     * @throws Exception
     */
    private function getPredisObject()
    {
        $predisAutoloaderPath = __DIR__ . '/predis/Autoloader.php';
        if (!file_exists($predisAutoloaderPath)) {
            throw new Exception('Predis library autoloader is not found!');
        }
        if (!class_exists('\\Predis\\Autoloader')) {
            require_once $predisAutoloaderPath;

            \Predis\Autoloader::register();
        }

        return new \Predis\Client($this->_pRedisConfig['servers'], $this->_pRedisOptions);
    }

    /**
     * 获取序列化类型
     * @return int
     */
    private function getSerializerType()
    {
        $redisOptSerializer = Redis::SERIALIZER_PHP;
        // 如果安装了快速序列化扩展，就使用扩展
        if ($this->is_supported_igbinary() && self::$_config['use_igbinary'] === true) {
            $redisOptSerializer = Redis::SERIALIZER_IGBINARY;
        }
        return $redisOptSerializer;
    }

    /**
     * 设置过期时间后，才计数
     *
     * @param $key
     * @param $expireTime
     *
     * @return bool|int
     */
    public function setExpireIncr($key, $expireTime)
    {
        try {
            if ($this->getHandle() === null) throw new Exception('Cache: Redis connection failed. Check your configuration.');
            // 该key数量+1
            $result = $this->_redis->incr($key);
            $ttl = $this->_redis->ttl($key);
            // 没有设置过期时间，或者原本key不存在. ttl = -1 没有设置过期时间 or -2 key不存在
            if ($result && $ttl == -1) {
                $this->_redis->expire($key, $expireTime);
            }
        } catch (Exception $e) {
            $result = false;
            logWarning(__METHOD__ . ' = ' . $e->getMessage());
        }
        return $result;
    }

    /**
     * @param $scriptKey
     * @param array|string $keys 键名
     * @param array|string $args 参数
     *
     * @return bool|mixed
     * @throws Exception
     *
     * @example
     * client->evalScript('incr', 'test', 100); // incr, key, 过期时间
     * 增量计数器，超过最大值就重置为0
     * client->evalScript('incr_reset', 'test', [maxCounter, 100]); // incr_reset, key, [最大值, 过期时间]
     * 增量计数器，如果当前值没有大于限定值，才可以加一并返回[1, 累加后的值]，否则返回[0, 当前值]
     * client->evalScript('incr_max', 'test', [maxCounter, 100]); // incr_max, key, [最大值, 过期时间]
     *
     */
    public function evalScript($scriptKey, $keys, $args = [])
    {
        // 如果不是用PHPRedis扩展
        if (!$this->_isPhpRedis) {
            // 如果是incr，就用另外的方式实现
            if ($scriptKey == 'incr') {
                return $this->setExpireIncr($keys, $args);
            }
            throw new Exception(__METHOD__ . ' 该客户端暂不支持该方式，请使用PHPRedis客户端');
        }
        if (!isset($this->_funcTable[$scriptKey]) || empty($this->_funcTable[$scriptKey]['script'])) {
            throw new Exception(__METHOD__ . ' 请先配置' . $scriptKey . '脚本');
        }
        if (empty($this->_funcTable[$scriptKey]['sha1'])) {
            $this->_funcTable[$scriptKey]['sha1'] = sha1($this->_funcTable[$scriptKey]['script']);
        }
        $sha1 = $this->_funcTable[$scriptKey]['sha1'];
        $result = false;
        try {
            if ($this->getHandle() === null) throw new Exception('Cache: Redis connection failed. Check your configuration.');

            if (!is_array($keys)) {
                $keys = [$keys];
            } else {
                // 不需要键名索引，用数字重新建立索引
                $keys = array_values($keys);
            }
            if (!is_array($args)) {
                $args = [$args];
            } else {
                // 不需要键名索引，用数字重新建立索引
                $args = array_values($args);
            }
            $keyCount = count($keys);
            $args = array_merge($keys, $args);
            for ($i = 0; $i < 2; $i++) {
                $result = $this->_redis->evalSha($sha1, $args, $keyCount);
                if ($result === false && $i === 0) {
                    $errorMsg = $this->_redis->getLastError();
                    $this->_redis->clearLastError();
                    // 该脚本不存在该节点上，需要执行load
                    if (stripos($errorMsg, 'NOSCRIPT') !== false) {
                        // 单机
                        if (self::$_config['client_type'] == self::CLIENT_TYPE_PHP_REDIS) {
                            $loadParams = [
                                'load',
                                $this->_funcTable[$scriptKey]['script']
                            ];
                        } // 集群
                        else {
                            // 取Key用来定位节点
                            $key = $keys;
                            if (is_array($keys)) {
                                $key = $keys[0];
                            }
                            $loadParams = [
                                $key,
                                'load',
                                $this->_funcTable[$scriptKey]['script']
                            ];
                        }
                        // load脚本
                        $serverSha1 = $this->_redis->script(...$loadParams);
                        // 在开发阶段解决这个错误
                        if ($serverSha1 !== $sha1) {
                            throw new Exception($scriptKey . '脚本的sha1与服务端返回的sha1不一致' . $sha1 . '==' . $serverSha1, 999);
                        }
                        continue;
                    }
                }
                break;
            }
        } catch (Exception $e) {
            // 如果是开发阶段能解决的错误，就抛出去
            if ($e->getCode() === 999) {
                throw new Exception($e->getMessage(), $e->getCode());
            }
            $result = false;
            logWarning(__METHOD__ . ' = ' . $e->getMessage());
        }
        return $result;
    }

    /**
     * Check if Redis driver is supported
     *
     * @return    bool
     */
    public function is_supported()
    {
        // 如果使用PRedis，可以不需要装Redis扩展
        return extension_loaded('redis');
    }

    /**
     * 检测是否支持igbinary扩展, 用于数据序列化
     * @return bool
     */
    public function is_supported_igbinary()
    {
        return extension_loaded('igbinary');
    }

    /**
     * 设置Key前缀
     *
     * @param $prefix
     */
    public function setPrefix($prefix)
    {
        $this->getHandle()->setOption(Redis::OPT_PREFIX, $prefix);
    }

    /**
     * 获取所有的客户端类型
     * @return array
     */
    private function getAllClientType()
    {
        return [self::CLIENT_TYPE_PHP_REDIS, self::CLIENT_TYPE_PHP_REDIS_CLUSTER, self::CLIENT_TYPE_PREDIS];
    }

    // ------------------------------------------------------------------------

    /**
     * Class destructor
     *
     * Closes the connection to Redis if present.
     *
     * @return    void
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * __call magic method
     *
     * Handles access to the parent driver library's methods
     *
     * @access    public
     *
     * @param    string
     * @param    array
     *
     * @return    mixed
     */
    public function __call($method, $args)
    {
        try {
            $handle = $this->getHandle();
            if ($handle === null) throw new Exception('Cache: Redis connection failed. Check your configuration.');
            return $handle->{$method}(...$args);
        } catch (Exception $e) {
            $this->logWarningMessage(__METHOD__ . ' = ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 关闭连接
     */
    public function close()
    {
        if ($this->_redis) {
            try {
                switch (self::$_config['client_type']) {
                    case self::CLIENT_TYPE_PHP_REDIS:
                        // 不是长连接
                        $this->_redis->close();
                        break;
                    case self::CLIENT_TYPE_PHP_REDIS_CLUSTER:
                        // 不是长连接
                        $this->_redis->close();
                        break;
                    case self::CLIENT_TYPE_PREDIS:
                        $this->_redis->disconnect();
                        break;
                }
            } catch (Exception $e) {
                $this->logWarningMessage(__METHOD__ . ' = ' . $e->getMessage());
            }
        }
        $this->_redis = null;
    }


    /**
     * 记录日志
     *
     * @param $content
     */
    public function logWarningMessage($content)
    {
        logWarning($content);
    }
}
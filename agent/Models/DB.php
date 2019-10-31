<?php
/**
 * Created by PhpStorm.
 * User: pandy
 * Date: 2019/9/19
 * Time: 10:53 PM
 */

namespace Models;

use function Libs\configItem;

class DB
{
    private static $_dbs = [];

    public static function init() {
        self::getInstance();
    }

    /**
     * 获取DB实例
     * 注意，此方法调用在worker/process/task进程中，所以每个进程都会需要调用一次
     *
     * @param string $name
     *
     * @return mixed
     * @throws \Exception
     */
    public static function getInstance($name='')
    {
        // 默认数据库
        $name = strtolower($name) ?: configItem('default_select_db');;
        if (isset(self::$_dbs[$name])) {
            return self::$_dbs[$name];
        }
        $dbConfig = configItem(null, null, 'db');
        $config = isset($dbConfig[$name]) ? $dbConfig[$name] : [];
        if (empty($config) || empty($config['dsn']) || empty($config['username'])) {
            throw new \Exception('请先配置[' . $name . ']数据库连接参数');
        }
        self::$_dbs[$name] = \Envms\FluentPDO\Factory::create($config);
        if (isset($config['debug'])) {
            self::$_dbs[$name]->debug = $config['debug'];
        }
        return self::$_dbs[$name];
    }
}
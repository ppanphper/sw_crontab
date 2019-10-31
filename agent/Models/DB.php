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

    protected static $_defaultDBName = null;

    public static function init() {
        self::getInstance();
        self::$_defaultDBName = configItem('default_select_db');
    }

    /**
     * 获取DB实例
     *
     * @param string $name
     *
     * @return mixed
     * @throws \Exception
     */
    public static function getInstance($name='')
    {
        // 默认数据库
        $name = strtolower($name) ?: self::$_defaultDBName;
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
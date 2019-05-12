<?php
namespace Envms\FluentPDO;

use function Libs\logError;
use \PDO;
use \PDOException;
use \Exception;

/**
 * Class Factory
 *
 * @package Envms\FluentPDO
 */
abstract class Factory
{
    /**
     * Create a new FluentPDO object based on PDO constructors
     *
     * @param array $dbConfig
     * @return FluentPDO
     * @throws \Exception
     */
    public static function create(array $dbConfig)
    {
        $pdo = self::getPDO($dbConfig);
        return new FluentPDO($pdo, $dbConfig);
    }

    /**
     * 获取PDO对象
     *
     * @param array $dbConfig
     *
     * @return PDO
     * @throws Exception
     */
    public static function getPDO(array $dbConfig) {
        if(empty($dbConfig['dsn']) || empty($dbConfig['username']) || !isset($dbConfig['password'])) {
            throw new Exception('缺少数据库连接参数');
        }

        if(!isset($dbConfig['options']) || !is_array($dbConfig['options'])) {
            $dbConfig['options'] = [];
        }
        $dsn = $dbConfig['dsn'];
        $username = $dbConfig['username'];
        $password = $dbConfig['password'];
        $options = $dbConfig['options'];
        $emulatePrepare = isset($dbConfig['emulatePrepare']) ? $dbConfig['emulatePrepare'] : null;

        $dbEngine = '';
        $post_query = null;

        if (!\is_string($username)) {
            $username = '';
        }
        if (!\is_string($password)) {
            $password = '';
        }

        // Let's grab the DB engine
        if (strpos($dsn, ':') !== false) {
            $dbEngine = explode(':', $dsn)[0];
        }

        /** @var string $post_query */
        $post_query = '';

        // If no charset is specified, default to UTF-8
        switch ($dbEngine) {
            case 'mysql':
                if (\strpos($dsn, ';charset=') === false) {
                    $dsn .= ';charset=utf8mb4';
                }
                $post_query = '';
                break;
            case 'pgsql':
                $post_query = 'SET NAMES UNICODE';
                break;
        }

        try {
            $pdo = new \PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            logError($e->getMessage());
            throw new \Exception(
                'Could not create a PDO connection. Please check your username and password.'
            );
        }

        if (!empty($post_query)) {
            $pdo->query($post_query);
        }

        if ($emulatePrepare !== null && constant('PDO::ATTR_EMULATE_PREPARES')) {
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, $emulatePrepare);
        }

        return $pdo;
    }
}

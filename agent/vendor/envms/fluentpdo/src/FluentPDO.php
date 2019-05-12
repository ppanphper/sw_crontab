<?php
/**
 * FluentPDO is simple and smart SQL query builder for PDO
 *
 * For more information @see readme.md
 *
 * @link      http://github.com/lichtner/fluentpdo
 * @author    Marek Lichtner, marek@licht.sk
 * @copyright 2012 Marek Lichtner
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2 (one or other)
 */

namespace Envms\FluentPDO;

use Libs\Server;
use \PDO;
use yii\db\Exception;

/**
 * Class FluentPDO
 */
class FluentPDO
{

    /** @var PDO */
    protected $pdo;
    /** @var FluentStructure|null */
    protected $structure;
    /** @var array 存储DB连接信息，用于重连 */
    protected $dbConfig = [];

    /** @var bool|callback */
    public $debug;

    /** @var boolean */
    public $convertTypes = false;

    protected $errorInfo = [];

    /**
     * FluentPDO constructor.
     *
     * @param PDO $pdo
     * @param array $dbConfig 数据库连接信息，用来重连
     * @param FluentStructure|null $structure
     */
    function __construct(PDO $pdo, array $dbConfig, FluentStructure $structure = null)
    {
        $this->pdo = $pdo;
        $this->dbConfig = $dbConfig;
        if (!$structure) {
            $structure = new FluentStructure();
        }
        $this->structure = $structure;
    }

    /**
     * Create SELECT query from $table
     *
     * @param string $table - db table name
     * @param integer $primaryKey - return one row by primary key
     *
     * @return SelectQuery
     */
    public function from($table, $primaryKey = null)
    {
        $query = new SelectQuery($this, $table);
        if ($primaryKey !== null) {
            $tableTable = $query->getFromTable();
            $tableAlias = $query->getFromAlias();
            $primaryKeyName = $this->structure->getPrimaryKey($tableTable);
            $query = $query->where("$tableAlias.$primaryKeyName", $primaryKey);
        }

        return $query;
    }

    /**
     * Create INSERT INTO query
     *
     * @param string $table
     * @param array $values - accepts one or multiple rows, @see docs
     *
     * @return InsertQuery
     */
    public function insertInto($table, $values = array())
    {
        $query = new InsertQuery($this, $table, $values);

        return $query;
    }

    /**
     * Create UPDATE query
     *
     * @param string $table
     * @param array|string $set
     * @param string $primaryKey
     *
     * @return UpdateQuery
     */
    public function update($table, $set = array(), $primaryKey = null)
    {
        $query = new UpdateQuery($this, $table);
        $query->set($set);
        if ($primaryKey) {
            $primaryKeyName = $this->getStructure()->getPrimaryKey($table);
            $query = $query->where($primaryKeyName, $primaryKey);
        }

        return $query;
    }

    /**
     * Create DELETE query
     *
     * @param string $table
     * @param string $primaryKey delete only row by primary key
     *
     * @return DeleteQuery
     */
    public function delete($table, $primaryKey = null)
    {
        $query = new DeleteQuery($this, $table);
        if ($primaryKey) {
            $primaryKeyName = $this->getStructure()->getPrimaryKey($table);
            $query = $query->where($primaryKeyName, $primaryKey);
        }

        return $query;
    }

    /**
     * Create DELETE FROM query
     *
     * @param string $table
     * @param string $primaryKey
     *
     * @return DeleteQuery
     */
    public function deleteFrom($table, $primaryKey = null)
    {
        $args = func_get_args();

        return call_user_func_array(array($this, 'delete'), $args);
    }

    public function setErrorInfo(array $errorInfo) {
        $this->errorInfo = $errorInfo;
    }

    public function getErrorInfo() {
        return $this->errorInfo;
    }

    /**
     * 重新连接数据库
     *
     * @return bool
     */
    public function reconnect()
    {
        $bool = true;
        if($this->pdo) {
            $this->pdo = null;
        }
        try {
            $pdo = Factory::getPDO($this->dbConfig);
            $this->pdo = $pdo;
        } catch (Exception $e) {
            $bool = false;
        }
        return $bool;
    }

    /**
     * @return \PDO
     */
    public function getPdo()
    {
        return $this->pdo;
    }

    /**
     * @return FluentStructure
     */
    public function getStructure()
    {
        return $this->structure;
    }

    /**
     * Closes the \PDO connection to the database
     *
     * @return null
     */
    public function close()
    {
        $this->pdo = null;
    }

}

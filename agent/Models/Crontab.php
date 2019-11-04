<?php

namespace Models;

use Libs\Constants;

/**
 * This is the model class for table "crontab".
 *
 * @property string $id
 * @property integer $cid
 * @property string $name
 * @property string $desc
 * @property string $rule
 * @property integer $concurrency
 * @property string $command
 * @property integer $max_process_time
 * @property integer $timeout_opt
 * @property integer $log_opt
 * @property integer $retries
 * @property integer $retry_interval
 * @property integer $status
 * @property string $run_user
 * @property integer $notice_way
 * @property integer $create_time
 * @property integer $update_time
 */
class Crontab extends DB
{
    const STATUS_DISABLED = 0; // 停用
    const STATUS_ENABLED = 1; // 启用

    const TIME_OUT_OPT_IGNORE = 0; // 超时 - 忽略
    const TIME_OUT_OPT_KILL = 1; // 超时 - 强杀

    const LOG_OPT_IGNORE = 0; // 日志选项 - 忽略
    const LOG_OPT_WRITE_FILE = 1; // 日志选项 - 写入文件

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'crontab';
    }

    /**
     * 获取当前节点需要执行的任务数据
     *
     * @param int $agentId 节点Id
     * @param int $status 任务状态 1=启用 0=停用
     * @param array $loadSpecifiedIds 加载指定任务Id记录
     * @param int $offset 偏移量
     * @param int $limit 限制读取数量
     *
     * @return array|bool|\PDOStatement
     * @throws \Exception
     *
     * TODO 注意此处的SQL如果遇到数据过多会变慢，可以分批查出在程序里合并过滤
     */
    public static function getCurrentNodeTaskData($agentId = 0, $loadSpecifiedIds = [], $status = self::STATUS_ENABLED, $offset = 0, $limit = 1000)
    {
        // 获取指定为本节点或未指定节点的任务，并排除指定不在此节点上运行的任务
        $condition = 'a.status = :status AND (b.bid = :agentId OR b.bid IS NULL) AND a.id NOT IN (SELECT aid FROM via_table WHERE `type` = :notInAgents AND bid = :notInAgentId)';
        $params = [
            ':status'       => $status,
            ':agentId'      => $agentId,
            ':notInAgents'  => Constants::TYPE_CRONTAB_NOT_IN_AGENTS,
            ':notInAgentId' => $agentId,
        ];
        /**
         * SELECT a.* FROM crontab a
         * LEFT JOIN via_table b ON a.id = b.aid AND b.type = 2
         * WHERE a.status = 1 AND (b.bid = 1 OR b.bid IS NULL)
         * AND a.id NOT IN (SELECT aid FROM via_table WHERE `type` = 3 AND bid = 1)
         */
        $query = self::getInstance()->from(self::tableName() . ' a')
//            ->select(null)
//            ->select('DISTINCT (a.id), a.*')
            ->leftJoin('via_table b ON a.id = b.aid AND b.type = ' . Constants::TYPE_CRONTAB_AGENTS);
        if ($loadSpecifiedIds) {
            $query->where('a.id', $loadSpecifiedIds);
        }
        return $query->where($condition, $params)
            ->offset($offset)
            ->limit($limit)
            ->fetchAll();

    }

}

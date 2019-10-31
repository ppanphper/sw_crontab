<?php
/**
 * Created by PhpStorm.
 * User: pandy
 * Date: 2019/10/8
 * Time: 1:38 PM
 */
namespace Models;

use Libs\Constants;

class User extends DB
{
    const STATUS_DISABLED = 0; // 停用
    const STATUS_ENABLED = 1; // 启用

    /**
     * 根据任务Id获取责任人
     *
     * @param $taskId
     *
     * @return array|bool|\PDOStatement
     * @throws \Exception
     */
    public static function getUserDataByTaskId($taskId) {
        return self::getInstance()
            ->from('via_table a')
            ->select(null)
            ->select('b.nickname, b.mobile, b.email')
            ->innerJoin('user b ON b.id = a.bid AND a.type = ' . Constants::TYPE_CRONTAB_OWNER)->where([
                'a.aid'    => $taskId,
                'b.status' => self::STATUS_ENABLED,
            ])->fetchAll();
    }
}
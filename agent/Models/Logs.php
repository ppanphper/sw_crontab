<?php

namespace Models;

/**
 * This is the model class for table "logs".
 *
 * @property string $id
 * @property integer $task_id
 * @property string $run_id
 * @property int $code
 * @property string $title
 * @property string $msg
 * @property integer $consume_time
 * @property integer $created
 */
class Logs extends DB
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'logs';
    }

    /**
     * @param array $data
     * [
        'task_id'      => $data['taskId'],
        'run_id'       => $data['runId'],
        'code'         => $data['code'],
        'title'        => $data['title'],
        'msg'          => $data['msg'],
        'consume_time' => $data['consumeTime'],
        'created'      => $data['created'],
        ]
     *
     * @return int
     * @throws \Exception
     */
    public static function saveLog(array $data)
    {
        return self::getInstance()->insertInto(self::tableName(), [
            'task_id'      => $data['taskId'],
            'run_id'       => $data['runId'],
            'code'         => $data['code'],
            'title'        => $data['title'],
            'msg'          => $data['msg'],
            'consume_time' => $data['consumeTime'],
            'created'      => $data['created'],
        ])->execute();
    }

}

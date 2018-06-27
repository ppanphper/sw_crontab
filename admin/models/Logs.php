<?php

namespace app\models;

use Yii;

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
class Logs extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'logs';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['task_id', 'run_id', 'created'], 'required'],
            [['task_id', 'run_id', 'created', 'code', 'consume_time'], 'integer'],
            [['msg'], 'string'],
            [['title'], 'string', 'max' => 64],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id'           => Yii::t('app', 'ID'),
            'task_id'      => Yii::t('app', 'Task ID'),
            'run_id'       => Yii::t('app', 'Run ID'),
            'code'         => Yii::t('app', 'Status Code'),
            'title'        => Yii::t('app', 'Title'),
            'msg'          => Yii::t('app', 'Msg'),
            'consume_time' => Yii::t('app', 'Consume Time'),
            'created'      => Yii::t('app', 'Created'),
        ];
    }
}

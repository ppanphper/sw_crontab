<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "via_table".
 *
 * @property integer $id
 * @property integer $aid
 * @property integer $bid
 * @property integer $type 1=agent_category 2=crontab_agent 3=crontab_owner
 */
class ViaTable extends \yii\db\ActiveRecord
{
    // 分类
    const TYPE_AGENTS_CATEGORY = 1;
    // 指定运行的节点
    const TYPE_CRONTAB_AGENTS = 2;
    // 不在这些节点下运行任务
    const TYPE_CRONTAB_NOT_IN_AGENTS = 3;
    // 负责人
    const TYPE_CRONTAB_OWNER = 4;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'via_table';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['aid', 'bid', 'type'], 'required'],
            [['aid', 'bid', 'type'], 'integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'aid' => Yii::t('app', 'Aid'),
            'bid' => Yii::t('app', 'Bid'),
            'type' => Yii::t('app', 'Type'),
        ];
    }
}


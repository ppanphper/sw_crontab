<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "agents_category".
 *
 * @property integer $id
 * @property integer $cid
 * @property integer $aid
 */
class AgentsCategory extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'agents_category';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['cid', 'aid'], 'required'],
            [['cid', 'aid'], 'integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id'  => Yii::t('app', 'ID'),
            'cid' => Yii::t('app', 'Cid'),
            'aid' => Yii::t('app', 'Aid'),
        ];
    }
}

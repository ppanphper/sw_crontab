<?php

namespace app\models;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "category".
 *
 * @property integer $id
 * @property string $name
 */
class Category extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'category';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name'], 'string', 'max' => 30],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id'   => Yii::t('app', 'ID'),
            'name' => Yii::t('app', 'Name'),
        ];
    }

    /**
     * 获取所有分类数据
     * @return array
     */
    public static function getAllData()
    {
        static $data;
        if (is_null($data)) {
            $rows = static::find()->all();
            $data = [];
            if (!empty($rows)) {
                $data = ArrayHelper::map($rows, "id", "name");
            }
        }
        return $data;
    }
}

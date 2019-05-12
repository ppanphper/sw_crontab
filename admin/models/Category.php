<?php

namespace app\models;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "category".
 *
 * @property integer $id
 * @property string $name
 * @property integer $status
 */
class Category extends \yii\db\ActiveRecord
{
    const STATUS_DISABLED = 0; // 停用
    const STATUS_ENABLED = 1; // 启用

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
            ['status', 'integer'],
            [['name'], 'string', 'max' => 30],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'name' => Yii::t('app', 'Name'),
            'status' => Yii::t('app', 'Status'),
        ];
    }

    /**
     * 获取所有分类数据
     *
     * @param array|integer|string|null $id
     *
     * @return array
     */
    public static function getData($id=null) {
        static $data = [];
        $key = $id === null ? 'all' : md5(serialize($id));
        if(empty($data[$key])) {
            $condition = [
                'status' => self::STATUS_ENABLED
            ];
            if ($id) {
                $id = (is_string($id) && strpos($id, ',') !== false) ? explode(',', $id) : $id;
                $condition['id'] = $id;
            }
            $data[$key] = static::findAll($condition);
        }
        return $data[$key];
    }

    /**
     * 获取下拉列表数据
     *
     * @param string|array|integer|null $id
     *
     * @return array
     */
    public static function getDropDownListData($id=null) {
        $data = [];
        $rows = self::getData($id);
        if($rows) {
            foreach($rows as $row) {
                $data[$row['id']] = $row['name'];
            }
        }
        if($id === null) {
            return $data;
        }
        $result = [];
        if($id) {
            $ids = [$id];
            if(is_array($id)) {
                $ids = $id;
            }
            else if(is_string($id) && strpos($id, ',') !== false) {
                $ids = explode(',', $id);
            }
            foreach($ids as $id) {
                if(isset($data[$id])) {
                    $result[$id] = $data[$id];
                }
            }
        }
        return $result;
    }
}

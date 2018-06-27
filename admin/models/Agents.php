<?php

namespace app\models;

use app\commands\MonitorController;
use Yii;
use yii\db\Exception;

/**
 * This is the model class for table "agents".
 *
 * @property integer $id
 * @property string $name
 * @property string $ip
 * @property integer $port
 * @property integer $status
 */
class Agents extends \yii\db\ActiveRecord
{
    const STATUS_DISABLED = 0; // 停用
    const STATUS_ENABLED = 1; // 启用

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'agents';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'ip', 'port', 'status'], 'required'],
            [['port', 'status'], 'integer'],
            [['name'], 'string', 'max' => 30],
            [['ip'], 'string', 'max' => 50],
            // 1.0.0.0 ~ 255.255.255.255
            ['ip', 'match', 'pattern' => '/^(?:25[0-5]|2[0-4]\d|1\d{2}|[1-9]\d?)\.(?:(?:25[0-5]|2[0-4]\d|1\d{2}|[1-9]?\d)\.){2}(?:25[0-5]|2[0-4]\d|1\d{2}|[1-9]?\d)$/'],
            ['port', 'compare', 'compareValue' => 1024, 'operator' => '>='],
            ['port', 'compare', 'compareValue' => 65535, 'operator' => '<='],
            // ip和端口唯一
            [['ip', 'port'], 'unique', 'targetAttribute' => ['ip', 'port'], 'message' => Yii::t('app', 'The combination {values} has already been taken.')],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id'          => Yii::t('app', 'ID'),
            'name'        => Yii::t('app', 'Name'),
            'ip'          => Yii::t('app', 'Ip'),
            'port'        => Yii::t('app', 'Port'),
            'status'      => Yii::t('app', 'Status'),
            'categoryIds' => Yii::t('app', 'Category'),
        ];
    }

    public function saveData($params)
    {
        if ($this->load($params)) {
            $transaction = self::getDb()->beginTransaction();
            try {
                if ($this->save()) {
                    if (!empty($this->id)) {
                        AgentsCategory::deleteAll(['aid' => $this->id]);
                    }

                    $categoryIds = [];
                    $scope = $this->formName();
                    if ($scope === '') {
                        $categoryIds = $params['categoryIds'];
                    } elseif (isset($params[$scope])) {
                        $categoryIds = $params[$scope]['categoryIds'];
                    }
                    if (!empty($categoryIds) && is_array($categoryIds)) {
                        $data = [];
                        foreach ($categoryIds as $cid) {
                            $data[] = [
                                'cid' => intval($cid),
                                'aid' => $this->id,
                            ];
                        }
                        self::getDb()->createCommand()->batchInsert(AgentsCategory::tableName(), ['cid', 'aid'], $data)->execute();
                    }
                    $transaction->commit();
                    return true;
                }
            } catch (Exception $e) {
                $transaction->rollBack();
                Yii::warning(__METHOD__ . ' Save data failed = ' . $e->getMessage());
                $this->addError('status', Yii::t('app', 'Save data failed'));
            }
        }
        return false;
    }

    public function getCategory()
    {
        // hasMany要求返回两个参数 第一个参数是关联表的类名 第二个参数是两张表的关联关系
        // 这里aid是AgentsCategory表的关联字段, id是当前Agents模型的主键
        return $this->hasMany(Category::className(), ['id' => 'cid'])
            ->viaTable(AgentsCategory::tableName(), ['aid' => 'id']);
    }

    public function getCategoryIds()
    {
        $data = [];
        if ($this->category) {
            foreach ($this->category as $item) {
                $data[] = $item['id'];
            }
        }
        return $data;
    }

    /**
     * 获取所有服务器数据
     *
     * @param array|integer|string|null $id
     *
     * @return array
     */
    public static function getData($id=null)
    {
        $condition = [
            'status' => self::STATUS_ENABLED
        ];
        if ($id) {
            $id = (is_string($id) && strpos($id, ',') !== false) ? explode(',', $id) : $id;
            $condition['id'] = $id;
        }
        return static::findAll($condition);
    }

    /**
     * 获取下拉列表数据
     *
     * @param string|array|integer|null $id
     *
     * @return array
     */
    public static function getDropDownListData($id=null) {
        static $data = [];
        if(empty($data)) {
            $rows = self::getData();
            if($rows) {
                foreach($rows as $row) {
                    $data[$row['id']] = $row['name'].'('.$row['ip'].')';
                }
            }
        }
        if($id === null) {
            return $data;
        }
        $result = [];
        if($id) {
            $ids = [$id];
            if(is_string($id) && strpos($id, ',') !== false) {
                $ids = explode(',', $id);
            }
            foreach($ids as $id) {
                $result[$id] = isset($data[$id]) ? $data[$id] : '未知('.$id.')';
            }
        }
        return $result;
    }

    /**
     * 获取心跳时间
     *
     * @param $ip
     * @param $port
     *
     * @return string
     * @throws \yii\base\InvalidConfigException
     * @see MonitorController::scanNodes()
     */
    public function getHeartbeatTime($ip, $port)
    {
        static $config = null;
        if (is_null($config)) {
            $config = [];
            $configPath = Yii::$app->basePath . '/config/agent_node.php';
            if (file_exists($configPath)) {
                $config = include $configPath;
            }
        }
        $key = $ip . ':' . $port;
        return isset($config[$key]) ? Yii::$app->formatter->asDatetime($config[$key]['time']) : '未知';
    }
}

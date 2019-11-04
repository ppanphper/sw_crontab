<?php

namespace app\models;

use app\commands\MonitorController;
use app\config\Constants;
use Yii;
use \Exception;
use \Throwable;

/**
 * This is the model class for table "agents".
 *
 * @property integer $id
 * @property string $name
 * @property string $ip
 * @property integer $port
 * @property integer $status
 * @property integer $last_report_time
 * @property integer $agent_status
 */
class Agents extends \yii\db\ActiveRecord
{
    const STATUS_DISABLED = 0; // 停用
    const STATUS_ENABLED = 1; // 启用

    const AGENT_STATUS_OFFLINE = 0; // 离线
    const AGENT_STATUS_ONLINE = 1; // 在线
    const AGENT_STATUS_ONLINE_REPORT_FAILED = 2; // 在线, 但是Redis没有上报

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
            [['name','ip','port', 'status'], 'required'],
            [['port', 'status'], 'integer'],
            [['name'], 'string', 'max' => 30],
            [['ip'], 'string', 'max' => 50],
            // 1.0.0.0 ~ 255.255.255.255
            ['ip', 'match', 'pattern' => '/^(?:(?:25[0-5]|2[0-4]\d|1\d{2}|[1-9]?\d)\.){3}(?:25[0-5]|2[0-4]\d|1\d{2}|[1-9]?\d)$/'],
            ['port', 'compare', 'compareValue'=>1024, 'operator'=>'>='],
            ['port', 'compare', 'compareValue'=>65535, 'operator'=>'<='],
            // ip和端口唯一
            [['ip','port'], 'unique', 'targetAttribute' => ['ip', 'port'], 'message' => Yii::t('app', 'The combination {values} has already been taken.')],
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
            'ip' => Yii::t('app', 'Ip'),
            'port' => Yii::t('app', 'Port'),
            'status' => Yii::t('app', 'Status'),
            'agent_status' => Yii::t('app', 'Agent Status'),
            'categoryId' => Yii::t('app', 'Category'),
            'last_report_time' => Yii::t('app', 'Heartbeat'),
        ];
    }

    public function saveData($params) {
        if($this->load($params)) {
            $transaction = self::getDb()->beginTransaction();
            try {
                if($this->save()) {
                    if(!empty($this->id)) {
                        ViaTable::deleteAll(['aid'=>$this->id, 'type' => ViaTable::TYPE_AGENTS_CATEGORY]);
                    }

                    $categoryId = [];
                    $scope = $this->formName();
                    if ($scope === '') {
                        $categoryId = $params['categoryId'];
                    } elseif (isset($params[$scope])) {
                        $categoryId = $params[$scope]['categoryId'];
                    }
                    $data = [];
                    if(!empty($categoryId)) {
                        if(is_array($categoryId)) {
                            $records = [];
                            foreach($categoryId as $id) {
                                if($id) {
                                    $records[$id] = [
                                        'aid'=> $this->id,
                                        'bid'=> $id,
                                        'type' => ViaTable::TYPE_AGENTS_CATEGORY
                                    ];
                                }
                            }
                            $data = array_values($records);
                        }
                        else {
                            $data[] = [
                                'aid'=> $this->id,
                                'bid'=> $categoryId,
                                'type' => ViaTable::TYPE_AGENTS_CATEGORY
                            ];
                        }
                    }
                    if($data) {
                        for ($i = 0, $total = count($data); $i < $total; $i += 100)
                        {
                            self::getDb()->createCommand()->batchInsert(ViaTable::tableName(), ['aid', 'bid', 'type'], array_slice($data, $i, 100))->execute();
                        }
                    }
                    $transaction->commit();
                    return true;
                }
            } catch (Exception $e) {
                $transaction->rollBack();
                Yii::warning(__METHOD__ .' Save data failed = '.$e->getMessage());
                $this->addError('status', Yii::t('app', 'Save data failed'));
            } catch(Throwable $e) {
                $transaction->rollBack();
                Yii::warning(__METHOD__ .' Save data failed = '.$e->getMessage());
            }
        }
        return false;
    }

    public function getCategory()
    {
        return $this->hasMany(ViaTable::class, ['aid'=>'id'])->onCondition(['b.type' => ViaTable::TYPE_AGENTS_CATEGORY])->alias('b');
        // hasMany要求返回两个参数 第一个参数是关联表的类名 第二个参数是两张表的关联关系
        // 这里aid是ViaTable表的关联字段, id是Agents表的主键
        // bid是ViaTable表关联Category表的关联字段, id是Category表的主键
        // type = ViaTable::TYPE_AGENTS_CATEGORY 表示查询Agents和Category表的关联记录
//		return $this->hasMany(Category::class, ['id'=>'bid'])->alias('c')
//			->viaTable(ViaTable::tableName() . ' b', ['aid'=>'id'], function ($query){
//				$query->onCondition(['b.type' => ViaTable::TYPE_AGENTS_CATEGORY]);
//			});
    }

    public function getCategoryDetail()
    {
        // hasMany要求返回两个参数 第一个参数是关联表的类名 第二个参数是两张表的关联关系
        // 这里aid是ViaTable表的关联字段, id是Agents表的主键
        // bid是ViaTable表关联Category表的关联字段, id是Category表的主键
        // type = ViaTable::TYPE_AGENTS_CATEGORY 表示查询Agents和Category表的关联记录
        return $this->hasMany(Category::class, ['id'=>'bid'])->alias('c')
            ->viaTable(ViaTable::tableName() . ' b', ['aid'=>'id'], function ($query) {
                $query->onCondition(['b.type' => ViaTable::TYPE_AGENTS_CATEGORY]);
            });
    }

    public function getCategoryId() {
        $data = [];
        if($this->category) {
            foreach($this->category as $item) {
//				$data[] = $item['id'];
                $data[] = $item['bid'];
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
                $data[$row['id']] = $row['name'].'('.$row['ip'].')';
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

//	/**
//	 * 获取心跳时间
//	 *
//	 * @param $ip
//	 * @param $port
//	 *
//	 * @return string
//	 * @throws \yii\base\InvalidConfigException
//	 * @see MonitorController::scanNodes()
//	 */
//	public function getHeartbeatTime($ip, $port)
//	{
//		static $config = null;
//		if (is_null($config)) {
//			$config = [];
//			$configPath = Yii::$app->basePath . '/config/agent_node.php';
//			if (file_exists($configPath)) {
//				$config = include $configPath;
//			}
//		}
//		$key = $ip . ':' . $port;
//		$lastReportTime = isset($config[$key]) ? $config[$key]['time'] : '';
//		$result = '<span class="text-red">['. Yii::t('app', 'Offline') . ']</span> ';
//		if($lastReportTime) {
//			// 当前时间 减去 最后上报时间 <= 离线时间 = 正常
//			if((time() - $lastReportTime) <= Yii::$app->params['offlineTime']) {
//				$result = '<span class="text-green">['. Yii::t('app', 'Normal') .']</span> ';
//			}
//			$result .= Yii::$app->formatter->asDatetime($lastReportTime);
//		}
//		return $result;
//	}

    /**
     * 有记录变更，就通知agent更新
     *
     * @param bool $insert
     * @param array $changedAttributes
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        $oldAttributes = $this->getOldAttributes();
        $checkChangeFields = [
//            'ip',
//            'port',
            'status'
        ];
        foreach($checkChangeFields as $field) {
            // 如果状态有变更，通知Agent重新加载节点信息
            if(isset($changedAttributes[$field]) && $changedAttributes[$field] != $oldAttributes[$field]) {
//                $ip = isset($changedAttributes['ip']) ? $changedAttributes['ip'] : $oldAttributes['ip'];
//                $port = isset($changedAttributes['port']) ? $changedAttributes['port'] : $oldAttributes['port'];
                $ip = $oldAttributes['ip'];
                $port = $oldAttributes['port'];
                $key = Constants::REDIS_KEY_AGENT_CHANGE_MD5 . $ip .'_'.$port;
                Yii::$app->redis->set($key, microtime(true), 86400);
                break;
            }
        }
    }
}

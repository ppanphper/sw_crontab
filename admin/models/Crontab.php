<?php

namespace app\models;

use app\config\Constants;
use Yii;
use yii\base\DynamicModel;
use yii\db\ActiveRecord;
use \Exception;
use \Throwable;

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
class Crontab extends ActiveRecord
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
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['cid', 'name', 'rule', 'command'], 'required'],
            [['cid', 'concurrency', 'max_process_time', 'retries', 'retry_interval', 'status', 'notice_way', 'create_time', 'update_time', 'timeout_opt', 'log_opt'], 'integer'],
            // 最大执行时间、并发数、重试次数、重试时间间隔设置默认值
            [['max_process_time', 'concurrency', 'retries', 'retry_interval', 'timeout_opt', 'log_opt'], 'default', 'value' => 0],
            // 默认分钟级
            ['rule', 'default', 'value' => '* * * * *'],
            // 通知方式
            ['notice_way', 'default', 'value' => Constants::NOTICE_WAY_SEND_MAIL],

            // 创建任务时，自动填充创建时间
            [['create_time', 'update_time'], 'filter', 'filter' => function ($value) {
                return time();
            }, 'on'                                             => ['create']],
            // 更新任务时，自动填充更新时间
            ['update_time', 'filter', 'filter' => function ($value) {
                return time();
            }, 'on'                            => ['update']],
            // 判断最大长度
            ['name', 'string', 'max' => 64],
            ['command', 'string', 'max' => 512],
            ['desc', 'string', 'max' => 255],
            ['command', function ($attr) {
                $value = $this->{$attr};
                // 如果有单、双引号，就判断单、双引号是否成对出现
                if (preg_match('/[\'"]/', $value)) {
                    $doubleQuoteCount = substr_count($value, '"');
                    $singleQuoteCount = substr_count($value, "'");
                    // 如果是奇数，就返回false
                    $boolean = (($doubleQuoteCount & 1) || ($singleQuoteCount & 1));
                    if ($boolean) {
                        $this->addError($attr, Yii::t('app', 'Quotes must appear in pairs'));
                    }
                }
                return true;
            }],
            ['command', 'match', 'pattern' => Constants::CMD_PARSE_PATTERN, 'message' => Yii::t('app', 'CMD parse failed')],

            [['rule', 'run_user'], 'string', 'max' => 600],
            ['rule', function ($attr) {
                $value = $this->{$attr};
                if (!preg_match(Constants::CRON_RULE_PATTERN, trim($value))) {
                    $this->addError($attr, Yii::t('app', 'Invalid cron rule'));
                }
                return true;
            }],

            // 最大执行时间、并发数、重试时间间隔只允许输入0~99999999之间的数值
            [['max_process_time', 'concurrency', 'retry_interval'], 'number', 'min' => 0, 'max' => 99999999],
            ['retries', 'number', 'min' => 0, 'max' => 255],

            // 不允许使用root运行任务
//            ['run_user', 'compare', 'compareValue' => 'root', 'operator' => '!='],
            // 遍历每个参数判断是否是整数型
//            [['owner', 'agents', 'notin_agents'], 'each', 'rule' => ['integer']],
            // 把字段的值转成字符串型
//            [
//                ['owner', 'agents', 'notin_agents'], 'filter', 'filter' => function ($value) {
//                return is_array($value) ? implode(',', $value) : $value;
//            }
//            ],
            // 判断是否超出最大长度
//            [['owner', 'agents', 'notin_agents'], 'string', 'max' => 255, 'on' => ['create', 'update']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id'               => Yii::t('app', 'ID'),
            'cid'              => Yii::t('app', 'Category Name'),
            'name'             => Yii::t('app', 'Name'),
            'desc'             => Yii::t('app', 'Desc'),
            'rule'             => Yii::t('app', 'Rule'),
            'concurrency'      => Yii::t('app', 'Concurrency'),
            'max_process_time' => Yii::t('app', 'Max Process Time'),
            'timeout_opt'      => Yii::t('app', 'Time out option'),
            'log_opt'          => Yii::t('app', 'Log out option'),
            'retries'          => Yii::t('app', 'Retries'),
            'retry_interval'   => Yii::t('app', 'Retry interval'),
            'command'          => Yii::t('app', 'Command'),
            'status'           => Yii::t('app', 'Status'),
            'run_user'         => Yii::t('app', 'Run User'),
//            'owner'            => Yii::t('app', 'Owner'),
//            'agents'           => Yii::t('app', 'Agents'),
//            'notin_agents'     => Yii::t('app', 'Not in agents'),
            'notice_way'       => Yii::t('app', 'Notice Way'),
            'create_time'      => Yii::t('app', 'Create Time'),
            'update_time'      => Yii::t('app', 'Update Time'),
            'ownerId'      => Yii::t('app', 'Owner'),
            'agentId'      => Yii::t('app', 'Agents'),
            'notInAgentId' => Yii::t('app', 'Filter nodes'),
        ];
    }

    /**
     * 保存数据
     *
     * @param $params
     *
     * @return bool
     * @throws \yii\db\Exception
     */
    public function saveData($params)
    {
        if ($this->load($params)) {
            $transaction = self::getDb()->beginTransaction();
            try {
                if ($this->save()) {
                    if (!empty($this->id)) {
                        ViaTable::deleteAll(['aid' => $this->id, 'type' => ViaTable::TYPE_CRONTAB_AGENTS]);
                        ViaTable::deleteAll(['aid' => $this->id, 'type' => ViaTable::TYPE_CRONTAB_OWNER]);
                        ViaTable::deleteAll(['aid' => $this->id, 'type' => ViaTable::TYPE_CRONTAB_NOT_IN_AGENTS]);
                    }

                    $ownerId = $agentId = $notInAgentId = [];
                    $scope = $this->formName();
                    if ($scope === '') {
                        $ownerId = $params['ownerId'];
                        $agentId = $params['agentId'];
                        $notInAgentId = $params['notInAgentId'];
                    } elseif (isset($params[$scope])) {
                        $ownerId = $params[$scope]['ownerId'];
                        $agentId = $params[$scope]['agentId'];
                        $notInAgentId = $params[$scope]['notInAgentId'];
                    }
                    $data = [];

                    $model = DynamicModel::validateData(compact('ownerId', 'agentId', 'notInAgentId'), [
                        ['ownerId', 'required', 'message' => Yii::t('app', 'Cannot be blank')],
                        [['ownerId', 'agentId', 'notInAgentId'], 'each', 'rule' => ['integer'], 'message' => Yii::t('app', 'Must be an integer')],
                    ]);

                    if ($model->hasErrors()) {
                        $this->addErrors($model->getErrors());
                        // 验证失败
                        throw new Exception(Yii::t('app', 'Failed validation form'));
                    }
                    if (!empty($ownerId)) {
                        if (is_array($ownerId)) {
                            $records = [];
                            foreach ($ownerId as $id) {
                                if ($id) {
                                    $records[$id] = [
                                        'aid'  => $this->id,
                                        'bid'  => $id,
                                        'type' => ViaTable::TYPE_CRONTAB_OWNER
                                    ];
                                }
                            }
                            $data = array_merge($data, array_values($records));
                        } else {
                            $data[] = [
                                'aid'  => $this->id,
                                'bid'  => $ownerId,
                                'type' => ViaTable::TYPE_CRONTAB_OWNER
                            ];
                        }
                    }
                    if (!empty($agentId)) {
                        if (is_array($agentId)) {
                            $records = [];
                            foreach ($agentId as $id) {
                                if ($id) {
                                    $records[$id] = [
                                        'aid'  => $this->id,
                                        'bid'  => $id,
                                        'type' => ViaTable::TYPE_CRONTAB_AGENTS
                                    ];
                                }
                            }
                            $data = array_merge($data, array_values($records));
                        } else {
                            $data[] = [
                                'aid'  => $this->id,
                                'bid'  => $agentId,
                                'type' => ViaTable::TYPE_CRONTAB_AGENTS
                            ];
                        }
                    }
                    if (!empty($notInAgentId)) {
                        if (is_array($notInAgentId)) {
                            $records = [];
                            foreach ($notInAgentId as $id) {
                                if ($id) {
                                    $records[$id] = [
                                        'aid'  => $this->id,
                                        'bid'  => $id,
                                        'type' => ViaTable::TYPE_CRONTAB_NOT_IN_AGENTS
                                    ];
                                }
                            }
                            $data = array_merge($data, array_values($records));
                        } else {
                            $data[] = [
                                'aid'  => $this->id,
                                'bid'  => $notInAgentId,
                                'type' => ViaTable::TYPE_CRONTAB_NOT_IN_AGENTS
                            ];
                        }
                    }
                    if ($data) {
                        for ($i = 0, $total = count($data); $i < $total; $i += 100) {
                            self::getDb()->createCommand()->batchInsert(ViaTable::tableName(), ['aid', 'bid', 'type'], array_slice($data, $i, 100))->execute();
                        }
                    }
                    $transaction->commit();
                    return true;
                }
            } catch (Exception $e) {
                $transaction->rollBack();
                Yii::warning(__METHOD__ . ' Save data failed = ' . $e->getMessage());
            } catch (Throwable $e) {
                $transaction->rollBack();
                Yii::warning(__METHOD__ . ' Save data failed = ' . $e->getMessage());
            }
        }
        return false;
    }

    public function getCategory()
    {
        return $this->hasOne(Category::class, ['id' => 'cid']);
    }

    public function getAgents()
    {
        return $this->hasMany(ViaTable::class, ['aid' => 'id'])->onCondition(['b.type' => ViaTable::TYPE_CRONTAB_AGENTS])->alias('b');
    }

    public function getOwners()
    {
        return $this->hasMany(ViaTable::class, ['aid' => 'id'])->onCondition(['c.type' => ViaTable::TYPE_CRONTAB_OWNER])->alias('c');
    }

    public function getNotInAgents()
    {
        return $this->hasMany(ViaTable::class, ['aid' => 'id'])->onCondition(['d.type' => ViaTable::TYPE_CRONTAB_NOT_IN_AGENTS])->alias('d');
    }

    /**
     * 获取负责人Id
     * 新增/编辑的时候使用
     *
     * @return array
     */
    public function getOwnerId()
    {
        $data = [];
        if ($this->owners) {
            foreach ($this->owners as $item) {
                $data[] = $item['bid'];
            }
        } // 新增页面负责人默认值
        else if ($this->getScenario() === self::SCENARIO_DEFAULT) {
            $data[] = Yii::$app->user->getIdentity()->getId();
        }
        return $data;
    }

    /**
     * 获取节点Id
     * 新增/编辑的时候使用
     *
     * @return array
     */
    public function getAgentId()
    {
        $data = [];
        if ($this->agents) {
            foreach ($this->agents as $item) {
                $data[] = $item['bid'];
            }
        }
        return $data;
    }

    /**
     * 获取过滤的节点Id
     * 新增/编辑的时候使用
     *
     * @return array
     */
    public function getNotInAgentId()
    {
        $data = [];
        if ($this->notInAgents) {
            foreach ($this->notInAgents as $item) {
                $data[] = $item['bid'];
            }
        }
        return $data;
    }

    /**
     * 有记录变更，就通知agent更新
     * TODO 假如更新redis失败？如果通知节点更新？用DB代替Redis?
     *
     * @param bool $insert
     * @param array $changedAttributes
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        // 标识有更新，客户端扫描到有更新
        Yii::$app->redis->set(Constants::REDIS_KEY_CRONTAB_CHANGE_MD5, md5(microtime(true) . mt_rand(0, 1000000)), 86400);
        /**
         * 存放crontab id hash表，值为更新时间
         * 节点扫描是否有更新，如果存在这个任务，并且上次更新时间小于当前时间，就更新
         * 如果这条域记录已经超过10分钟了，就删除，防止hash表过大
         */
        Yii::$app->redis->hset(Constants::REDIS_KEY_HASH_CRONTAB_CHANGE, $this->id, $this->update_time);
        Yii::$app->redis->expire(Constants::REDIS_KEY_HASH_CRONTAB_CHANGE, 86400);
    }
}

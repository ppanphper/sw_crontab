<?php

namespace app\models;

use app\config\Constants;
use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "crontab".
 *
 * @property string $id
 * @property integer $cid
 * @property string $name
 * @property string $rule
 * @property integer $concurrency
 * @property string $command
 * @property integer $max_process_time
 * @property integer $status
 * @property string $run_user
 * @property string $owner
 * @property string $agents
 * @property integer $notice_way
 * @property integer $create_time
 * @property integer $update_time
 */
class Crontab extends ActiveRecord
{
    const STATUS_DISABLED = 0; // 停用
    const STATUS_ENABLED = 1; // 启用

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
            [['cid', 'name', 'rule', 'command', 'owner'], 'required'],
            [['cid', 'concurrency', 'max_process_time', 'status', 'notice_way', 'create_time', 'update_time'], 'integer'],
            // 设置默认值
            ['concurrency', 'default', 'value' => 0],
            // 最大执行时间 默认值600秒
            ['max_process_time', 'default', 'value' => 600],
            // 默认分钟级
            ['rule', 'default', 'value' => '* * * * *'],
            // 通知方式
            ['notice_way', 'default', 'value' => Constants::NOTICE_WAY_SEND_MAIL],

            // 创建任务时，自动填充创建时间
            [['create_time', 'update_time'], 'filter', 'filter'=>function($value){return time();}, 'on'=>['create']],
            // 更新任务时，自动填充更新时间
            ['update_time', 'filter', 'filter'=>function($value){return time();}, 'on'=>['update']],
            // 判断最大长度
            ['name', 'string', 'max' => 64],
            ['command', 'string', 'max' => 512],
            ['command', function($attr) {
                $value = $this->{$attr};
                // 如果有单、双引号，就判断单、双引号是否成对出现
                if(preg_match('/[\'"]/', $value)) {
                    $doubleQuoteCount = substr_count($value,'"');
                    $singleQuoteCount = substr_count($value,"'");
                    // 如果是奇数，就返回false
                    $boolean = (($doubleQuoteCount & 1) || ($singleQuoteCount & 1));
                    if($boolean) {
                        $this->addError($attr, Yii::t('app', 'Quotes must appear in pairs'));
                    }
                }
                return true;
            }],
            ['command', 'match', 'pattern' => Constants::CMD_PARSE_PATTERN, 'message'=> Yii::t('app', 'CMD parse failed')],

            [['rule', 'run_user'], 'string', 'max' => 600],
            ['rule', function ($attr) {
                $value = $this->{$attr};
                if (!preg_match(Constants::CRON_RULE_PATTERN, trim($value))) {
                    $this->addError($attr, Yii::t('app', 'Invalid cron rule'));
                }
                return true;
            }],

            // 最大执行时间、并发数只允许输入0~99999999之间的数值
            [['max_process_time', 'concurrency'], 'number', 'min' => 0, 'max' => 99999999],

            // 不允许使用root运行任务
//            ['run_user', 'compare', 'compareValue'=>'root', 'operator'=>'!='],
            // 遍历每个参数判断是否是整数型
            [['owner', 'agents'], 'each', 'rule' => ['integer']],
            // 把字段的值转成字符串型
            [
                ['owner', 'agents'], 'filter', 'filter' => function($value) {
                    return is_array($value) ? implode(',', $value) : $value;
                }
            ],
            // 判断是否超出最大长度
            [['owner', 'agents'], 'string', 'max' => 255, 'on'=>['create', 'update']],
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
            'rule'             => Yii::t('app', 'Rule'),
            'concurrency'      => Yii::t('app', 'Concurrency'),
            'max_process_time' => Yii::t('app', 'Max Process Time'),
            'command'          => Yii::t('app', 'Command'),
            'status'           => Yii::t('app', 'Status'),
            'run_user'         => Yii::t('app', 'Run User'),
            'owner'            => Yii::t('app', 'Owner'),
            'agents'           => Yii::t('app', 'Agents'),
            'notice_way'       => Yii::t('app', 'Notice Way'),
            'create_time'      => Yii::t('app', 'Create Time'),
            'update_time'      => Yii::t('app', 'Update Time'),
        ];
    }

    public function getCategory()
    {
        return $this->hasOne(Category::className(), ['id' => 'cid']);
    }

    /**
     * 有记录变更，就通知agent更新
     *
     * @param bool $insert
     * @param array $changedAttributes
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        Yii::$app->redis->set(Constants::REDIS_KEY_CRONTAB_CHANGE_MD5, md5(microtime(true)), 86400);
    }
}

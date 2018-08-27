<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * This is the model class for table "user".
 *
 * @property int $id 自增ID
 * @property string $username 用户名
 * @property string $nickname 昵称
 * @property string $auth_key 自动登录key
 * @property string $password 加密密码
 * @property string $accessToken 访问令牌
 * @property string $mobile 手机号
 * @property string $email 邮箱
 * @property int $status 状态 0=禁用 1=启用
 * @property int $create_time 创建时间
 * @property int $update_time 更新时间
 */
class User extends ActiveRecord implements IdentityInterface
{
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['username', 'nickname', 'email'], 'required'],
            [['status', 'create_time', 'update_time'], 'integer'],
            [['username', 'nickname'], 'string', 'max' => 50],
            ['auth_key', 'string', 'max' => 32],

            ['auth_key', 'default', 'value' => ''],
            // 创建时，自动填充创建时间
            ['create_time', 'filter', 'filter'=>function($value){return time();}, 'on'=>['create']],
            // 更新时，自动填充更新时间
            ['update_time', 'filter', 'filter'=>function($value){return time();}, 'on'=>['create', 'update']],

            ['email', 'string', 'max' => 255],
            [['username', 'nickname', 'password'], 'trim'],
            ['password', 'required', 'on' => ['create']],

            [['password', 'accessToken'], 'string', 'max' => 100],

            ['mobile', 'string', 'max' => 20],
            ['email', 'email'],

            [['username'], 'unique'],
            [['nickname'], 'unique'],

            ['password', 'filter', 'filter' => function ($value) {
                if ($value) {
                    $value = Yii::$app->getSecurity()->generatePasswordHash($value);
                }
                return $value;
            }],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id'          => Yii::t('app', 'ID'),
            'username'    => Yii::t('app', 'Username'),
            'nickname'    => Yii::t('app', 'Nickname'),
            'auth_key'    => Yii::t('app', 'Auth Key'),
            'password'    => Yii::t('app', 'Password'),
            'accessToken' => Yii::t('app', 'Access Token'),
            'mobile'      => Yii::t('app', 'Mobile'),
            'email'       => Yii::t('app', 'Email'),
            'status'      => Yii::t('app', 'Status'),
            'create_time' => Yii::t('app', 'Create Time'),
            'update_time' => Yii::t('app', 'Update Time'),
        ];
    }

    /**
     * @inheritdoc
     * 根据user_backend表的主键（id）获取用户
     */
    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * Finds user by username
     *
     * @param  string $username
     *
     * @return array|null|ActiveRecord
     */
    public static function findByUsername($username)
    {
        $user = User::find()->where(['username' => $username, 'status' => self::STATUS_ACTIVE])->one();

        return $user;
    }

    /**
     * @inheritdoc
     * 根据access_token获取用户
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::findOne(['access_token' => $token, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * @inheritdoc
     * 用以标识 Yii::$app->user->id 的返回值
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 是否是激活用户
     *
     * @return bool
     */
    public function getIsActive()
    {
        return $this->status == self::STATUS_ACTIVE;
    }

    /**
     * @inheritdoc
     * 获取auth_key
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * @inheritdoc
     * 验证auth_key
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Validates password
     *
     * @param  string $password password to validate
     *
     * @return boolean if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return Yii::$app->getSecurity()->validatePassword($password, $this->password);
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * @param $id
     *
     * @return static[]
     */
    public static function getData($id=null)
    {
        static $data = [];
        $key = $id === null ? 'all' : md5(serialize($id));
        if(empty($data[$key])) {
            $condition = [
                'status' => self::STATUS_ACTIVE
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
     * @param null $id
     *
     * @return array
     */
    public static function getDropDownListData($id = null)
    {
        $data = [];
        $rows = self::getData($id);
        if($rows) {
            foreach($rows as $row) {
                $data[$row['id']] = $row['nickname'] ?: $row['username'];
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

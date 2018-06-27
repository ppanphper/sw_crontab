<?php

namespace app\models;

use Yii;
use yii\base\Model;

/**
 * LoginForm is the model behind the login form.
 *
 * @property User|null $user This property is read-only.
 *
 */
class PasswordForm extends Model
{
    public $password;
    public $newPassword;
    public $confirmPassword;

    private $_user = false;


    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['password', 'newPassword', 'confirmPassword'], 'required'],
            // password is validated by validatePassword()
            ['password', 'validatePassword'],
            ['confirmPassword', 'compare', 'compareAttribute' => 'newPassword'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'password'        => Yii::t('app', 'Original password'),
            'newPassword'     => Yii::t('app', 'New password'),
            'confirmPassword' => Yii::t('app', 'Confirm password'),
        ];
    }

    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     *
     * @param string $attribute the attribute currently being validated
     * @param array $params the additional name-value pairs given in the rule
     */
    public function validatePassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();

            if (!$user || !$user->validatePassword($this->password)) {
                $this->addError($attribute, Yii::t('app', 'Please enter the correct password.'));
            }
        }
    }

    /**
     * Finds user by [[username]]
     *
     * @return User|bool
     */
    public function getUser()
    {
        if ($this->_user === false) {
            $this->_user = Yii::$app->user->getIdentity();
        }

        return $this->_user;
    }

    public function changePassword()
    {
        $model = $this->getUser();
        $model->password = $this->newPassword;
        return $model->save(true, ['password', 'update_time']);
    }
}

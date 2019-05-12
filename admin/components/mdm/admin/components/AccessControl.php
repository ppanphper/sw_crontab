<?php

namespace app\components\mdm\admin\components;

use Yii;

/**
 * 扩展rbac访问控制，只有启用的用户才可以访问
 *
 * Created by PhpStorm.
 * User: pandy
 * Date: 2018/1/17
 * Time: 下午5:12
 */
class AccessControl extends \mdm\admin\components\AccessControl
{
    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        $actionId = $action->getUniqueId();
        $user = $this->getUser();
        // 如果是登录用户，但是该用户在该系统中被禁用了，就不允许访问
        if ((!$user->getIsGuest() || (method_exists($user, 'getIsActive') && $user->getIsActive())) && Helper::checkRoute('/' . $actionId, Yii::$app->getRequest()->get(), $user)) {
            return true;
        }
        $this->denyAccess($user);
    }
}
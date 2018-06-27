<?php
/**
 * Created by PhpStorm.
 * User: pandy
 * Date: 2018/1/17
 * Time: 下午3:48
 */

namespace app\components;

use Yii;
use yii\rbac\Item;
use yii\rbac\Rule;

/**
 * 访问规则，只有启用的用户才可以访问
 *
 * Class AccessRule
 * @package app\components
 */
class AccessRule extends Rule
{

    /**
     * @param string|integer $user 当前登录用户的uid
     * @param Item $item 所属规则rule，也就是我们后面要进行的新增规则
     * @param array $params 当前请求携带的参数.
     *
     * @return bool true或false.true用户可访问 false用户不可访问
     */
    public function execute($user, $item, $params)
    {
        $boolean = false;
        if (Yii::$app->user->getIsGuest()) {
            $boolean = true;
            // 如果是禁用的用户，就不允许访问
            if (method_exists(Yii::$app->user, 'getIsActive')) {
                if (!Yii::$app->user->getIsActive()) {
                    $boolean = false;
                }
            }
        }
        return $boolean;
    }
}
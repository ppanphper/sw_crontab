<?php
/**
 * Created by PhpStorm.
 * User: pandy
 * Date: 2018/1/8
 * Time: 下午5:01
 */

namespace app\components;

use Yii;

class Menu extends \dmstr\widgets\Menu
{
    protected $noDefaultAction;
    protected $noDefaultRoute;

    public function run()
    {
        // 如果是登录用户，但是该用户被禁用，就不显示菜单
        if (method_exists(Yii::$app->user, 'getIsActive') && Yii::$app->user->getIsGuest() && !Yii::$app->user->getIsActive()) {
            return;
        }
        parent::run();
    }

    /**
     * Checks whether a menu item is active.
     * This is done by checking if [[route]] and [[params]] match that specified in the `url` option of the menu item.
     * When the `url` option of a menu item is specified in terms of an array, its first element is treated
     * as the route for the item and the rest of the elements are the associated parameters.
     * Only when its route and parameters match [[route]] and [[params]], respectively, will a menu item
     * be considered active.
     *
     * @param array $item the menu item to be checked
     *
     * @return boolean whether the menu item is active
     */
    protected function isItemActive($item)
    {
        if (isset($item['url']) && is_array($item['url']) && isset($item['url'][0])) {
            $route = $item['url'][0];
            if ($route[0] !== '/' && Yii::$app->controller) {
                $route = ltrim(Yii::$app->controller->module->getUniqueId() . '/' . $route, '/');
            }
            $route = ltrim($route, '/');
            $arrayRoute = explode('/', $route);
            $arrayThisRoute = explode('/', $this->route);
            //改写了路由的规则，是否高亮判断到controller而非action
            $routeCount = count($arrayRoute);
            $match = true;
            for ($i = 0; $i < $routeCount - 1; $i++) {
                if ($arrayRoute[$i] != $arrayThisRoute[$i]) {
                    $match = false;
                    break;
                }
            }

            if (!$match && $route != $this->route && $route !== $this->noDefaultRoute && $route !== $this->noDefaultAction) {
                return false;
            }

            unset($item['url']['#']);
            if (count($item['url']) > 1) {
                foreach (array_splice($item['url'], 1) as $name => $value) {
                    if ($value !== null && (!isset($this->params[$name]) || $this->params[$name] != $value)) {
                        return false;
                    }
                }
            }
            return true;
        }
        return false;
    }
}
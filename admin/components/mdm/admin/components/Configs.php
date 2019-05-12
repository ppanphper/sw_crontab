<?php
/**
 * 解决\mdm\admin\components\Configs父类$_classes写死了cache的class
 * 或者忽略error 日志，在params.php配置
 *
 * 'mdm.admin.configs' => [
 *   'db' => 'customDb',
 *   'menuTable' => '{{%admin_menu}}',
 *   'cache' => [
 *       'class' => 'yii\caching\DbCache',
 *       'db' => ['dsn' => 'sqlite:@runtime/admin-cache.db'],
 *   ],
 * ]
 *
 * or use [[\Yii::$container]]
 *
 * ```
 * Yii::$container->set('mdm\admin\components\Configs',[
 *     'db' => 'customDb',
 *     'menuTable' => 'admin_menu',
 * ]);
 *
 * Created by PhpStorm.
 * User: pandy
 * Date: 2019/5/10
 * Time: 11:43 AM
 */

namespace app\components\mdm\admin\components;

use Yii;
use yii\di\Instance;

class Configs extends \mdm\admin\components\Configs
{
    protected static $_classes = [
        'db' => 'yii\db\Connection',
        'userDb' => 'yii\db\Connection',
        'cache' => 'ppanphper\redis\Cache',
        'authManager' => 'yii\rbac\ManagerInterface',
    ];

    /**
     * @inheritdoc
     */
    public function init()
    {
        foreach (self::$_classes as $key => $class) {
            try {
                $this->{$key} = empty($this->{$key}) ? null : Instance::ensure($this->{$key}, $class);
            } catch (\Exception $exc) {
                $this->{$key} = null;
                Yii::error($exc->getMessage());
            }
        }
    }
}
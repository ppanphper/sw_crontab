<?php
/**
 * Created by PhpStorm.
 * User: pandy
 * Date: 2018/1/19
 * Time: 下午1:47
 */

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\assets;

use yii\web\AssetBundle;

/**
 * This asset bundle provides the [jQuery](http://jquery.com/) JavaScript library.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class JqueryUIAsset extends AssetBundle
{
    public $sourcePath = '@bower/jquery-ui';
    public $css = [
        'themes/base/jquery-ui.min.css',
    ];
    public $js = [
        'jquery-ui.min.js',
    ];
    public $depends = [
        'yii\web\JqueryAsset'
    ];
}
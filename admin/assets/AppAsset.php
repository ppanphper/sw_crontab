<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\assets;

use yii\web\AssetBundle;
use yii\web\View;

/**
 * Main application asset bundle.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/site.css',
    ];
    public $js = [
        'js/common.js',
    ];
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap\BootstrapAsset',
    ];

    /**
     * 定义按需加载JS方法，注意加载顺序在最后
     *
     * @param View $view
     * @param $jsFile
     */
    public static function addScript(View $view, $jsFile)
    {
        $view->registerJsFile($jsFile, [AppAsset::className(), 'depends' => 'app\assets\AppAsset']);
    }

    /**
     * 定义按需加载css方法，注意加载顺序在最后
     *
     * @param View $view
     * @param $cssFile
     */
    public static function addCss(View $view, $cssFile)
    {
        $view->registerCssFile($cssFile, [AppAsset::className(), 'depends' => 'app\assets\AppAsset']);
    }
}

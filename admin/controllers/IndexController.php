<?php
/**
 * Created by PhpStorm.
 * User: pandy
 * Date: 2017/11/2
 * Time: 下午1:49
 */

namespace app\controllers;

use Yii;
use yii\web\Controller;

class IndexController extends Controller
{
    public function actionIndex()
    {
        return $this->render('index', [
        ]);
    }
}
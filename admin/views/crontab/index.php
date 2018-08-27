<?php

use yii\helpers\Html;
use yii\grid\GridView;
use app\models\Crontab;
use app\models\Category;
use app\models\User;
use app\models\Agents;
use dosamigos\selectize\SelectizeDropDownList;
use app\helpers\StringHelper;
use app\config\Constants;
use app\assets\AppAsset;

/* @var $this yii\web\View */
/* @var $searchModel app\models\searchs\Crontab */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Crontabs');
$this->params['breadcrumbs'][] = $this->title;
//$noticeWayMaps = Constants::NOTICE_WAY_MAPS;
//foreach ($noticeWayMaps as &$val) {
//	$val = Yii::t('app', $val);
//}
$categoryAllData = Category::getDropDownListData();
?>
<div class="crontab-index">
    <div class="mt-20"></div>
    <div class="row">
        <div class="col-xs-12">
            <?php
            echo $this->render('_search', [
                'model' => $searchModel,
                'categoryAllData' => $categoryAllData,
            ]);
            if($searchModel->agentId && $searchModel->agentInfo) {
            ?>
                <div class="row">
                    <div class="col-md-3">
                        <?= $this->render('_agentInfo', ['model' => $searchModel]);?>
                    </div>
                    <div class="col-md-9">
                        <?= $this->render('_indexTable', [
                            'model' => $searchModel,
                            'dataProvider' => $dataProvider,
                            'categoryAllData' => $categoryAllData,
                        ]);?>
                    </div>
                </div>
            <?php
            }
            else {
                echo $this->render('_indexTable', [
                    'model' => $searchModel,
                    'dataProvider' => $dataProvider,
                    'categoryAllData' => $categoryAllData,
                ]);
            }
            ?>
        </div>
        <!-- /.col -->
    </div>
    <!-- /.row -->
</div>

<?php

use yii\helpers\Html;
use yii\grid\GridView;
use app\models\User;

/* @var $this yii\web\View */
/* @var $searchModel app\models\searchs\User */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Users');
$this->params['breadcrumbs'][] = $this->title;
?>
<h1><?= $this->title; ?></h1>
<div class="user-index">
    <div class="row">
        <div class="col-xs-12">
            <div class="box table-responsive">
                <div class="box-header">
                    <p>
                        <?= Html::a(Yii::t('app', 'Create User'), ['create'], ['class' => 'btn btn-success']) ?>
                    </p>
                </div>
                <!-- /.box-header -->
                <div class="box-body">
                    <?= GridView::widget([
                        'dataProvider' => $dataProvider,
                        'filterModel'  => $searchModel,
                        'columns'      => [
                            'id',
                            'username',
                            'nickname',
                            'mobile',
                            'email:email',
                            // 状态
                            [
                                'format'    => 'html', // 此列内容输出时不会被转义
                                'filter'    => [ // 过滤器，也就是搜索框。该值为数组时会显示一个下拉框（dropdown list）
                                    User::STATUS_INACTIVE => Yii::t('app', 'Disabled'),
                                    User::STATUS_ACTIVE   => Yii::t('app', 'Enabled'),
                                ],
                                'attribute' => 'status', // 字段名
                                'value'     => function ($model) { // 该列内容
                                    if ($model->status == User::STATUS_INACTIVE) return '<span class="label label-danger">' . Yii::t('app', 'Disabled') . '</span>';
                                    if ($model->status == User::STATUS_ACTIVE) return '<span class="label label-success">' . Yii::t('app', 'Enabled') . '</span>';
                                },
                            ],
                            'update_time:datetime',

                            ['class' => 'yii\grid\ActionColumn'],
                        ],
                    ]); ?>
                </div>
                <!-- /.box-body -->
            </div>
            <!-- /.box -->
        </div>
        <!-- /.col -->
    </div>
    <!-- /.row -->
</div>


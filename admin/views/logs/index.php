<?php

use yii\helpers\Html;
use yii\grid\GridView;
use app\config\Constants;
use app\helpers\StringHelper;
use app\helpers\TimeHelper;
use \dosamigos\selectize\SelectizeDropDownList;

/* @var $this yii\web\View */
/* @var $searchModel app\models\searchs\Logs */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Logs');
$this->params['breadcrumbs'][] = $this->title;
$codeMaps = Constants::CUSTOM_CODE_MAPS;
$codeLabelMaps = [];
foreach ($codeMaps as $code => $label) {
    $codeLabelMaps[$code] = Yii::t('app', $label);
}
?>

<div class="logs-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <div class="row">
        <div class="col-xs-12">
            <div class="box table-responsive">
                <!-- /.box-header -->
                <div class="box-body" style="min-height: 300px;">
                    <?= GridView::widget([
                        'dataProvider' => $dataProvider,
                        'filterModel'  => $searchModel,
                        'tableOptions' => [
                            'class' => 'table table-striped table-bordered table-vertical-align-middle',
                        ],
                        'columns'      => [
                            [
                                'attribute'     => 'id',
                                'headerOptions' => [
                                    'width' => '100',
                                ],
                            ],
                            [
                                'attribute'      => 'task_id',
                                'format'         => ['html', ['Attr.AllowedFrameTargets' => ['_blank']]],
                                'contentOptions' => [
                                    'width' => '100',
                                ],
                                'value'          => function ($model) {
                                    return Html::a($model->task_id, ['crontab/view', 'id' => $model->task_id], ['target' => '_blank']);
                                }
                            ],
                            [
                                'attribute'      => 'run_id',
                                'format'         => ['html', ['Attr.AllowedFrameTargets' => ['_blank']]],
                                'contentOptions' => [
                                    'width' => '165',
                                ],
                                'enableSorting'  => false,
                                'value'          => function ($model) {
                                    return Html::a($model->run_id, ['logs/index', 'Logs[task_id]' => $model->task_id, 'Logs[run_id]' => $model->run_id], ['target' => '_blank']);
                                }
                            ],
                            [
                                'attribute'      => 'code',
                                'contentOptions' => [
                                    'width' => '150',
                                ],
                                'enableSorting'  => false,
                                'filter'         => SelectizeDropDownList::widget([
                                    'model'     => $searchModel,
                                    'attribute' => 'code',
                                    'items'     => $codeLabelMaps,
                                    'options'   => [
                                        'prompt' => Yii::t('app', 'Please select'),
                                    ],
                                ]),
                                'value'          => function ($model) use ($codeLabelMaps) { // 该列内容
                                    $content = $model->code;
                                    if (isset($codeLabelMaps[$model->code])) {
                                        $content = $codeLabelMaps[$model->code];
                                    }
                                    return $content;
                                },
                            ],
                            [
                                'attribute'      => 'consume_time',
                                'contentOptions' => [
                                    'width' => '80',
                                ],
                                'value'          => function ($model) { // 该列内容
                                    return TimeHelper::msTimeFormat($model->consume_time);
                                },
                            ],
                            [
                                'attribute'      => 'created',
                                'format'         => 'datetime',
                                'contentOptions' => [
                                    'width' => '180',
                                ],
                            ],
                            [
                                'attribute'     => 'msg',
                                'format'        => 'ntext',
                                'enableSorting' => false,
                                'value'         => function ($model) {
                                    return StringHelper::cutSubstring($model->msg, 100);
                                },
                            ],
                            [
                                'class'          => 'yii\grid\ActionColumn',
                                'template'       => '{view}',
                                'contentOptions' => [
                                    'width' => '40'
                                ],
                            ],
                        ],
                        'pager'        => [
                            'firstPageLabel' => Yii::t('app', 'First Page'),
                            'lastPageLabel'  => Yii::t('app', 'Last Page'),
                            'maxButtonCount' => 10
                        ]
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

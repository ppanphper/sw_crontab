<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use app\config\Constants;
use app\helpers\TimeHelper;

/* @var $this yii\web\View */
/* @var $model app\models\Logs */

$this->title = $model->title;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Logs'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$codeMaps = Constants::CUSTOM_CODE_MAPS;
$codeLabelMaps = [];
foreach ($codeMaps as $code => $label) {
    $codeLabelMaps[$code] = Yii::t('app', $label);
}
?>
<div class="logs-view">

    <p>
        <?= Html::a(Yii::t('app', 'Back'), ['#'], ['class' => 'btn btn-default button-back']); ?>
    </p>

    <div class="row">
        <div class="col-md-8">
            <div class="box table-responsive">
                <div class="box-header with-border">
                    <h3 class="box-title"><?= $model->title; ?></h3>
                </div>
                <!-- /.box-header -->
                <div class="box-body">
                    <?= DetailView::widget([
                        'model'      => $model,
                        'attributes' => [
                            [
                                'attribute'      => 'id',
                                'captionOptions' => [
                                    'width' => '10%',
                                ],
                            ],
                            [
                                'attribute' => 'task_id',
                                'format'    => ['html', ['Attr.AllowedFrameTargets' => ['_blank']]],
                                'value'     => function ($model) {
                                    return Html::a($model->task_id, ['crontab/view', 'id' => $model->task_id], ['target' => '_blank']);
                                }
                            ],
                            [
                                'attribute' => 'run_id',
                                'format'    => ['html', ['Attr.AllowedFrameTargets' => ['_blank']]],
                                'value'     => function ($model) {
                                    return Html::a($model->run_id, ['logs/index', 'Logs[run_id]' => $model->run_id], ['target' => '_blank']);
                                }
                            ],
                            [
                                'attribute' => 'code',
                                'value'     => function ($model) use ($codeLabelMaps) { // 该列内容
                                    $content = $model->code;
                                    if (isset($codeLabelMaps[$model->code])) {
                                        $content = $codeLabelMaps[$model->code];
                                    }
                                    return $content;
                                },
                            ],
                            [
                                'attribute' => 'consume_time',
                                'value'     => function ($model) { // 该列内容
                                    return TimeHelper::msTimeFormat($model->consume_time);
                                },
                            ],
//                        'title',
                            'msg:ntext',
                            'created:datetime',
                        ],
                    ]) ?>
                </div>
            </div>
            <!-- /.box -->
        </div>
    </div>
</div>

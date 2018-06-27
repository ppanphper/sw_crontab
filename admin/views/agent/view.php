<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use app\models\Agents;

/* @var $this yii\web\View */
/* @var $model app\models\Agents */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Agents'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="agents-view">

    <p>
        <?= Html::a(Yii::t('app', 'Back'), ['#'], ['class' => 'btn btn-default button-back']); ?>
        <?= Html::a(Yii::t('app', 'Update'), ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a(Yii::t('app', 'Delete'), ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data'  => [
                'confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
                'method'  => 'post',
            ],
        ]) ?>
    </p>
    <div class="row">
        <div class="col-md-6">
            <div class="box table-responsive">
                <div class="box-header with-border">
                    <h3 class="box-title"><?= $this->title; ?></h3>
                </div>
                <!-- /.box-header -->
                <div class="box-body">
                    <?= DetailView::widget([
                        'model'      => $model,
                        'template'   => '<tr><th{captionOptions} width="20%">{label}</th><td{contentOptions}>{value}</td></tr>',
                        'options'    => ['class' => 'table table-bordered detail-view'],
                        'attributes' => [
                            [
                                'label' => Yii::t('app', 'ID'),
                                'value' => $model->id,
                            ],
                            [
                                'format' => 'html',
                                'label'  => Yii::t('app', 'Category Name'),
                                'value'  => function ($model) {
                                    if ($model->category) {
                                        $html = '';
                                        foreach ($model->category as $item) {
                                            $html .= '<span class="label label-info">' . html::encode($item->name) . '</span>&nbsp;';
                                        }
                                        return $html;
                                    }
                                    return '<span class="not-set">' . Yii::t('yii', '(not set)') . '</span>';
                                },
                            ],
                            [
                                'label' => Yii::t('app', 'Name'),
                                'value' => $model->name,
                            ],
                            [
                                'label' => Yii::t('app', 'Ip'),
                                'value' => $model->ip,
                            ],
                            [
                                'label' => Yii::t('app', 'Port'),
                                'value' => $model->port,
                            ],
                            [
                                'format'     => 'html',
                                'label'      => Yii::t('app', 'Status'),
                                'attributes' => 'status',
                                'value'      => function ($model) {
                                    if ($model->status == Agents::STATUS_DISABLED) return '<span class="label label-danger">' . Yii::t('app', 'Disabled') . '</span>';
                                    if ($model->status == Agents::STATUS_ENABLED) return '<span class="label label-success">' . Yii::t('app', 'Enabled') . '</span>';
                                }
                            ],
                            // 心跳
                            [
                                'label' => Yii::t('app', 'Heartbeat'),
                                'value' => function ($model) {
                                    return $model->getHeartbeatTime($model->ip, $model->port);
                                },
                            ],
                        ],
                    ]) ?>
                </div>
            </div>
            <!-- /.box -->
        </div>
    </div>
</div>

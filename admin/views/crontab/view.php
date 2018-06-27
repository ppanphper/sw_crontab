<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use app\models\Crontab;
use app\models\User;
use app\models\Agents;
use app\config\Constants;

/* @var $this yii\web\View */
/* @var $model app\models\Crontab */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Crontabs'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$noticeWayMaps = Constants::NOTICE_WAY_MAPS;
?>
<div class="crontab-view">

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
        <div class="col-md-8">
            <div class="box table-responsive">
                <div class="box-header with-border">
                    <h3 class="box-title"><?= $this->title; ?></h3>
                </div>
                <!-- /.box-header -->
                <div class="box-body">
                    <?= DetailView::widget([
                        'model'      => $model,
                        'template'   => '<tr><th class="white-space-normal"{captionOptions}>{label}</th><td{contentOptions}>{value}</td></tr>',
                        'attributes' => [
                            'id',
                            [
                                'format' => 'html',
                                'label'  => Yii::t('app', 'Category Name'),
                                'value'  => function ($model) {
                                    if ($model->category) {
                                        return '<span class="label label-info">' . html::encode($model->category->name) . '</span>&nbsp;';
                                    }
                                    return '<span class="not-set">' . Yii::t('yii', '(not set)') . '</span>';
                                },
                            ],
                            'name',
                            'rule',
                            // 并发数
                            [
                                'format'    => 'html',
                                'attribute' => 'concurrency',
                                'value'     => function ($model) {
                                    return $model->concurrency == 0 ? Yii::t('app', 'Unlimited') : $model->concurrency;
                                }
                            ],
                            // 并发数
                            [
                                'format'    => 'html',
                                'attribute' => 'max_process_time',
                                'value'     => function ($model) {
                                    return $model->max_process_time == 0 ? Yii::t('app', 'Unlimited') : $model->max_process_time . '秒';
                                }
                            ],
                            // 命令
                            'command',
                            [
                                'format'    => 'html',
                                'attribute' => 'status',
                                'value'     => function ($model) {
                                    if ($model->status == Crontab::STATUS_DISABLED) return '<span class="label label-danger">' . Yii::t('app', 'Disabled') . '</span>';
                                    if ($model->status == Crontab::STATUS_ENABLED) return '<span class="label label-success">' . Yii::t('app', 'Enabled') . '</span>';
                                }
                            ],
                            'run_user',
                            [
                                'attribute' => 'owner',
                                'format'    => 'html',
                                'value'     => function ($model) {
                                    $html = '';
                                    if ($model->owner && ($data = User::getDropDownListData($model->owner))) {
                                        foreach ($data as $id => $name) {
                                            $html .= '<span class="label label-info">' . html::encode($name) . '</span>&nbsp;';
                                        }
                                    }
                                    return $html;
                                }
                            ],
                            [
                                'attribute' => 'agents',
                                'format'    => 'html',
                                'value'     => function ($model) {
                                    $html = '<ul class="list-unstyled">';
                                    if ($model->agents && ($data = Agents::getDropDownListData($model->agents))) {
                                        foreach ($data as $id => $name) {
                                            $html .= '<li><span class="label label-info">' . html::encode($name) . '</span></li>';
                                        }
                                    }
                                    $html .= '</ul>';
                                    return $html;
                                },
                            ],
                            [
                                'attribute' => 'notice_way',
                                'format'    => 'html',
                                'value'     => function ($model) use ($noticeWayMaps) {
                                    $html = '<ul class="list-unstyled">';
                                    if ($model->notice_way) {
                                        $label = $noticeWayMaps[$model->notice_way];
                                        if (empty($label)) {
                                            $label = 'Unknown way';
                                        }
                                        $html .= '<li><span class="label label-info">' . Yii::t('app', $label) . '</span></li>';
                                    }
                                    $html .= '</ul>';
                                    return $html;
                                },
                            ],
                            'create_time:datetime',
                            'update_time:datetime',
                        ],
                    ]) ?>
                </div>
            </div>
            <!-- /.box -->
        </div>
    </div>
</div>

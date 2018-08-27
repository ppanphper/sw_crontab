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
            'data' => [
                'confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
                'method' => 'post',
            ],
        ]) ?>
    </p>
    <div class="row">
        <div class="col-md-8">
            <div class="box table-responsive">
                <div class="box-header with-border">
                    <h3 class="box-title"><?= $this->title;?></h3>
                </div>
                <!-- /.box-header -->
                <div class="box-body">
                <?= DetailView::widget([
                    'model' => $model,
                    'template' => '<tr><th class="white-space-normal"{captionOptions}>{label}</th><td{contentOptions}>{value}</td></tr>',
                    'attributes' => [
                        'id',
                        [
                            'format'=> 'html',
                            'label' => Yii::t('app', 'Category Name'),
                            'value' => function ($model) {
                                if($model->category) {
                                    return '<span class="label label-primary mar-5">'.html::encode($model->category->name).'</span>&nbsp;';
                                }
                                return '<span class="not-set">' . Yii::t('yii', '(not set)') . '</span>';
                            },
                        ],
                        'name',
                        [
                            'attribute' => 'desc',
                            'visible' => $model->desc,
                        ],
                        'rule',
                        // 命令
                        'command',
                        // 并发数
                        [
                            'attribute' => 'concurrency',
                            'value' => function($model) {
                                return $model->concurrency == 0 ? Yii::t('app', 'Unlimited') : $model->concurrency;
                            }
                        ],
                        // 并发数
                        [
                            'attribute' => 'max_process_time',
                            'value' => function($model) {
                                return $model->max_process_time == 0 ? Yii::t('app', 'Unlimited') : $model->max_process_time . Yii::t('app','s');
                            }
                        ],
                        // 执行超时选项
                        [
                            'attribute' => 'timeout_opt',
                            'value' => function($model) {
                                return Yii::t('app',$model->timeout_opt == Crontab::TIME_OUT_OPT_IGNORE ? 'Ignore' : 'Kill');
                            }
                        ],
                        [
                            'attribute' => 'retries',
                            'value' => function($model) {
                                return $model->retries ?: Yii::t('app', 'No retry');
                            }
                        ],
                        [
                            'attribute' => 'retry_interval',
                            'visible' => $model->retries,
                            'value' => function($model) {
                                return $model->retry_interval ? ($model->retry_interval . Yii::t('app','s')) : Yii::t('app', 'Immediate retry');
                            }
                        ],
                        [
                            'attribute' => 'run_user',
                            'visible' => $model->run_user
                        ],
                        [
                            'attribute'=>'ownerId',
                            'format' => 'html',
                            'value' => function($model) {
                                $html = '<ul class="list-unstyled">';
                                if($model->ownerId && ($data = User::getDropDownListData($model->ownerId))) {
                                    foreach($data as $name) {
                                        $html .= '<li><span class="label label-primary">'.html::encode($name).'</span></li>';
                                    }
                                }
                                $html .= '</ul>';
                                return $html;
                            }
                        ],
                        [
                            'attribute'=>'agentId',
                            'format' => 'html',
                            'visible' => $model->agentId,
                            'value' => function($model) {
                                $html = '<ul class="list-unstyled">';
                                if($model->agentId && ($data = Agents::getDropDownListData($model->agentId))) {
                                    foreach($data as $name) {
                                        $html .= '<li><span class="label label-primary">'.html::encode($name).'</span></li>';
                                    }
                                }
                                $html .= '</ul>';
                                return $html;
                            },
                        ],
                        [
                            'attribute'=>'notInAgentId',
                            'format' => 'html',
                            'visible' => $model->notInAgentId,
                            'value' => function($model) {
                                $html = '<ul class="list-unstyled">';
                                if($model->notInAgentId && ($data = Agents::getDropDownListData($model->notInAgentId))) {
                                    foreach($data as $name) {
                                        $html .= '<li><span class="label label-warning">'.html::encode($name).'</span></li>';
                                    }
                                }
                                $html .= '</ul>';
                                return $html;
                            },
                        ],
                        [
                            'attribute'=>'notice_way',
                            'format' => 'html',
                            'value' => function($model) use($noticeWayMaps) {
                                $html = '<ul class="list-unstyled">';
                                if($model->notice_way) {
                                    $label = $noticeWayMaps[$model->notice_way];
                                    if(empty($label)) {
                                        $label = 'Unknown way';
                                    }
                                    $html .= '<li><span class="label label-primary">'.Yii::t('app', $label).'</span></li>';
                                }
                                $html .= '</ul>';
                                return $html;
                            },
                        ],
                        [
                            'format' => 'html',
                            'attribute' => 'status',
                            'value' => function($model) {
                                if($model->status == Crontab::STATUS_DISABLED) return '<span class="label label-danger mar-5">'.Yii::t('app','Disabled').'</span>';
                                if($model->status == Crontab::STATUS_ENABLED) return '<span class="label label-success mar-5">'.Yii::t('app','Enabled').'</span>';
                            }
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

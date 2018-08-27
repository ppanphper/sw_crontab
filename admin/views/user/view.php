<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use app\models\User;

/* @var $this yii\web\View */
/* @var $model app\models\User */

$this->title = $model->username;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Users'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-view">
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
        <div class="col-md-6">
            <div class="box table-responsive">
                <div class="box-header with-border">
                    <h3 class="box-title"><?= $this->title;?></h3>
                </div>
                <!-- /.box-header -->
                <div class="box-body">
                    <?= DetailView::widget([
                        'model' => $model,
                        'attributes' => [
                            'id',
                            'username',
                            'nickname',
                            'mobile',
                            'email:email',
                            [
                                'format' => 'html',
                                'label' => Yii::t('app', 'Status'),
                                'attributes' => 'status',
                                'value' => function($model) {
                                    if($model->status == User::STATUS_INACTIVE) return '<span class="label label-danger">'.Yii::t('app','Disabled').'</span>';
                                    if($model->status == User::STATUS_ACTIVE) return '<span class="label label-success">'.Yii::t('app','Enabled').'</span>';
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

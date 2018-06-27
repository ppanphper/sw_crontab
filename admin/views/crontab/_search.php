<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\searchs\Crontab */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="crontab-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'cid') ?>

    <?= $form->field($model, 'name') ?>

    <?= $form->field($model, 'rule') ?>

    <?= $form->field($model, 'concurrency') ?>

    <?php // echo $form->field($model, 'command') ?>

    <?php // echo $form->field($model, 'status') ?>

    <?php // echo $form->field($model, 'runuser') ?>

    <?php // echo $form->field($model, 'owner') ?>

    <?php // echo $form->field($model, 'agents') ?>

    <?php // echo $form->field($model, 'createtime') ?>

    <?php // echo $form->field($model, 'updatetime') ?>

    <div class="form-group">
        <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>

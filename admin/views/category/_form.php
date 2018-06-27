<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\BaseHtml;

/* @var $this yii\web\View */
/* @var $model app\models\Category */
/* @var $form yii\widgets\ActiveForm */
?>
<div class="category-form">
    <div class="row">
        <div class="col-md-6">
            <!-- Horizontal Form -->
            <div class="box box-info table-responsive">
                <div class="box-header with-border">
                    <h3 class="box-title"><?= $this->title ?></h3>
                </div>
                <!-- /.box-header -->
                <!-- form start -->

                <?php $form = ActiveForm::begin([
                    'options'    => [
                        'class' => 'form-horizontal',
                    ],
                    'fieldClass' => 'app\widgets\ActiveField',
                ]); ?>
                <div class="box-body">
                    <?= $form->field($model, 'name')->textInput(['maxlength' => true, 'placeholder' => Yii::t('app', 'Please enter category name')]) ?>
                </div>

                <div class="box-footer">
                    <?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success pull-right' : 'btn btn-primary pull-right']) ?>
                    <?= Html::Button(Yii::t('app', 'Back'), ['class' => 'btn btn-default button-back']) ?>
                </div>

                <?php ActiveForm::end(); ?>

            </div>
        </div>
    </div>
</div>

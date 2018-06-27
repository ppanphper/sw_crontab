<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\Agents;
use app\models\Category;
use yii\helpers\BaseHtml;
use dosamigos\selectize\SelectizeDropDownList;

/* @var $this yii\web\View */
/* @var $model app\models\Agents */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="agents-form">
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
                    'options'              => [
                        'class' => 'form-horizontal',
                    ],
                    'fieldClass'           => 'app\widgets\ActiveField',
                    'enableAjaxValidation' => true, //开启Ajax验证
//					'enableClientValidation'=>false //关闭客户端验证
                ]); ?>
                <div class="box-body">
                    <?= $form->field($model, 'categoryIds')->widget(SelectizeDropDownList::className(), [
                        'items'         => Category::getAllData(),
                        'options'       => [
                            'multiple' => 'multiple',
                            'style'    => 'display:none;',
                            'prompt'   => Yii::t('app', 'Please select'),
                        ],
                        'clientOptions' => [
                            'plugins'     => ['remove_button'],
                            'delimiter'   => ',',
                            'persist'     => false,
                            'maxItems'    => null,
                            'valueField'  => 'id',
                            'labelField'  => 'name',
                            'searchField' => ['name'],
                            'options'     => [],
                        ],
                    ])
                    ?>

                    <?= $form->field($model, 'name')->textInput(['maxlength' => true, 'placeholder' => Yii::t('app', 'Please enter the server name')]) ?>

                    <?= $form->field($model, 'ip')->textInput(['maxlength' => true, 'placeholder' => Yii::t('app', 'Please enter the server ip')]) ?>

                    <?= $form->field($model, 'port')->textInput(['placeholder' => Yii::t('app', 'Please enter the server port')]) ?>

                    <?= $form->field($model, 'status')->dropDownList([
                        Agents::STATUS_ENABLED  => Yii::t('app', 'Enabled'),
                        Agents::STATUS_DISABLED => Yii::t('app', 'Disabled'),
                    ]) ?>
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


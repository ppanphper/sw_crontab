<?php
/**
 * Created by PhpStorm.
 * User: pandy
 * Date: 2018/5/16
 * Time: 下午3:00
 */

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\User */
$userIdentity = Yii::$app->user->identity;

$this->title = Yii::t('app', 'Change password') . ': ' . $userIdentity->username;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Users'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $userIdentity->username, 'url' => ['view', 'id' => $userIdentity->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<div class="user-password">

    <div class="user-form">
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
                        'fieldClass' => 'app\widgets\ActiveField'
                    ]); ?>

                    <div class="box-body">
                        <?= $form->field($model, 'password')->passwordInput(['maxlength' => true, 'placeholder' => Yii::t('app', 'Please enter the original password')]) ?>

                        <?= $form->field($model, 'newPassword')->passwordInput(['maxlength' => true, 'placeholder' => Yii::t('app', 'Please enter the new password')]) ?>

                        <?= $form->field($model, 'confirmPassword')->passwordInput(['maxlength' => true, 'placeholder' => Yii::t('app', 'Please enter the confirm password')]) ?>
                    </div>

                    <div class="box-footer">
                        <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-primary pull-right']) ?>
                        <?= Html::Button(Yii::t('app', 'Back'), ['class' => 'btn btn-default button-back']) ?>
                    </div>
                    <?php ActiveForm::end(); ?>
                </div>
            </div>
        </div>
    </div>

</div>

<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use \dosamigos\selectize\SelectizeDropDownList;
use app\models\Crontab;
use app\models\User;
use app\models\Agents;

/* @var $this yii\web\View */
/* @var $model app\models\searchs\Crontab */
/* @var $form yii\widgets\ActiveForm */
$defaultPrompt = Yii::t('app', 'Please select');
$isSearched = false;
if(isset($model->isSearched)) {
    $isSearched = $model->isSearched;
}
?>

<div class="crontab-search">
    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
        'options'=> [
            'class' => 'form-horizontal smart-form',
            'id' => 'search-form',
        ],
        'fieldClass' => 'app\widgets\ActiveField',
    ]); ?>
    <div class="box box-info <?=!$isSearched ? 'collapsed-box' : '';?> cursor-pointer">
        <div class="box-header with-border" data-widget="collapse">
            <h5 class="box-title"><?=Yii::t('app', 'Search condition');?></h5>
            <div class="box-tools pull-right">
                <?= Html::a(Yii::t('app', 'Create Crontab'), ['create'], ['class' => 'btn btn-primary btn-sm search-create-btn']) ?>
                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa <?=!$isSearched ? 'fa-plus' : 'fa-minus';?>"></i></button>
            </div>
        </div>
        <!-- /.box-header -->
        <div class="box-body search-condition" <?=!$isSearched ? 'style="display:none;"' : '';?>>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <?= $form->field($model, 'name') ?>
                    </div>
                    <div class="form-group">
                        <?= $form->field($model, 'command') ?>
                    </div>
                    <div class="form-group">
                        <?= $form->field($model, 'max_process_time')->input('number') ?>
                    </div>
                </div>
                <!-- /.col -->
                <div class="col-md-6">
                    <div class="form-group">
                        <?=$form->field($model, 'cid')->widget(SelectizeDropDownList::class, [
                            'items' => $categoryAllData,
                            'options' => [
                                'prompt'=> $defaultPrompt,
                            ],
                        ])?>
                    </div>
                    <div class="form-group">
                        <?= $form->field($model, 'ownerId')->widget(SelectizeDropDownList::class, [
                            'items' => User::getDropDownListData(),
                            'options' => [
                                'prompt'=> $defaultPrompt,
                            ],

                        ])?>
                    </div>
                    <div class="form-group">
                        <?= $form->field($model, 'agentId')->widget(SelectizeDropDownList::class, [
                            'items' => Agents::getDropDownListData(),
                            'options' => [
                                'prompt'=> $defaultPrompt,
                            ],
                        ])?>
                    </div>
                    <div class="form-group">
                        <?= $form->field($model, 'status')->dropDownList([
                            Crontab::STATUS_ENABLED  => Yii::t('app', 'Enabled'),
                            Crontab::STATUS_DISABLED => Yii::t('app', 'Disabled'),
                        ],[
                            'prompt' => $defaultPrompt
                        ]) ?>
                    </div>
                </div>
                <!-- /.col -->
            </div>
            <!-- /.row -->
        </div>
        <!-- /.box-body -->
        <div class="box-footer" <?=!$isSearched ? 'style="display:none;"' : '';?>>
            <?= Html::submitButton(Yii::t('app', 'Search'), ['class' => 'btn btn-primary']) ?>
            <?= Html::resetButton(Yii::t('app', 'Reset'), ['class' => 'btn btn-default search-reset-btn']) ?>
        </div>
    </div>
    <?php ActiveForm::end(); ?>

</div>

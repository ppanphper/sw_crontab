<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\searchs\Agents as SearchAgents;
use dosamigos\selectize\SelectizeDropDownList;

/* @var $this yii\web\View */
/* @var $model app\models\searchs\Agents */
/* @var $form yii\widgets\ActiveForm */
$defaultPrompt = Yii::t('app', 'Please select');
$isSearched = false;
if(isset($model->isSearched)) {
    $isSearched = $model->isSearched;
}
?>

<div class="agents-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
        'options'=> [
            'class' => 'form-horizontal smart-form',
            'id' => 'crontab-search-form',
        ],
        'fieldClass' => 'app\widgets\ActiveField',
    ]); ?>
    <div class="box box-info <?=!$isSearched ? 'collapsed-box' : '';?> cursor-pointer">
        <div class="box-header with-border" data-widget="collapse">
            <h5 class="box-title"><?=Yii::t('app', 'Search condition');?></h5>
            <div class="box-tools pull-right">
                <?= Html::a(Yii::t('app', 'Create Agents'), ['create'], ['class' => 'btn btn-primary btn-sm search-create-btn']) ?>
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
                        <?= $form->field($model, 'agent_status')->dropDownList([
                            SearchAgents::AGENT_STATUS_OFFLINE => Yii::t('app', 'Offline'),
                            SearchAgents::AGENT_STATUS_ONLINE_REPORT_FAILED  => Yii::t('app', 'No heartbeat, but the nodes are normal'),
                            SearchAgents::AGENT_STATUS_ONLINE => Yii::t('app', 'Normal'),
                        ],[
                            'prompt' => $defaultPrompt
                        ]) ?>
                    </div>
                </div>
                <!-- /.col -->
                <div class="col-md-6">
                    <div class="form-group">
                        <?= $form->field($model, 'ip') ?>
                    </div>
                    <div class="form-group">
                        <?=$form->field($model, 'categoryId')->widget(SelectizeDropDownList::class, [
                            'items' => $categoryAllData,
                            'options' => [
                                'prompt'=> $defaultPrompt,
                            ],
                        ])?>
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

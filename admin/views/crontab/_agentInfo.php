<?php

/* @var $this yii\web\View */
/* @var $model app\models\searchs\Crontab */
/* @var $form yii\widgets\ActiveForm */
use app\helpers\CommonHelper;
?>

<div class="crontab-agent-info">
    <div class="box table-responsive  box-info">
        <div class="box-header with-border">
            <h3 class="box-title"><?= Yii::t('app', 'Agent Information'); ?></h3>

            <div class="box-tools">
                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
                <button type="button" class="btn btn-box-tool" data-widget="remove"><i class="fa fa-remove"></i></button>
            </div>
        </div>
        <div class="box-body no-padding">
            <ul class="nav nav-pills nav-stacked">
                <?php if(isset($model->agentInfo['task_total'])) {?>
                <li><a href="javascript:;">任务数: <?=$model->agentInfo['task_total']?></a></li>
                <?php }?>
                <?php if(isset($model->agentInfo['current_task_count'])) {?>
                <li><a href="javascript:;">本分钟待执行任务数: <?=$model->agentInfo['current_task_count']?></a></li>
                <?php }?>
                <li><a href="javascript:;"><span class="font-bold">CPU</span></a></li>
                <?php
                if(!empty($model->agentInfo['cpuInfo'])) {
                ?>
                    <li><a href="javascript:;">核数: <?=count($model->agentInfo['cpuInfo'])?>核</a></li>
                    <li><a href="javascript:;">负载(1分钟): <?=$model->agentInfo['sysLoadAvg'][0]?></a></li>
                    <li><a href="javascript:;">负载(5分钟): <?=$model->agentInfo['sysLoadAvg'][1]?></a></li>
                    <li><a href="javascript:;">负载(15分钟): <?=$model->agentInfo['sysLoadAvg'][2]?></a></li>
                <?php
                }
                ?>
                <li><a href="javascript:;"><span class="font-bold">内存</span></a></li>
                <?php
                if(!empty($model->agentInfo['memInfo']['MemTotal'])) {
                ?>
                    <li><a href="javascript:;">MemTotal: <?=CommonHelper::byteConvert(bcmul($model->agentInfo['memInfo']['MemTotal'], 1024))?></a></li>
                    <li><a href="javascript:;">MemFree: <?=CommonHelper::byteConvert(bcmul($model->agentInfo['memInfo']['MemFree'], 1024))?></a></li>
                    <li><a href="javascript:;">Buffers: <?=CommonHelper::byteConvert(bcmul($model->agentInfo['memInfo']['Buffers'], 1024))?></a></li>
                    <li><a href="javascript:;">Cached: <?=CommonHelper::byteConvert(bcmul($model->agentInfo['memInfo']['Cached'], 1024))?></a></li>
                    <li><a href="javascript:;">SwapTotal: <?=CommonHelper::byteConvert(bcmul($model->agentInfo['memInfo']['SwapTotal'], 1024))?></a></li>
                    <li><a href="javascript:;">SwapCached: <?=CommonHelper::byteConvert(bcmul($model->agentInfo['memInfo']['SwapCached'], 1024))?></a></li>
                    <li><a href="javascript:;">SwapFree: <?=CommonHelper::byteConvert(bcmul($model->agentInfo['memInfo']['SwapFree'], 1024))?></a></li>
                <?php
                }
                ?>
            </ul>
        </div>
        <!-- /.box-body -->
    </div>
</div>

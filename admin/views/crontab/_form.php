<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\Category;
use app\models\Crontab;
use app\models\User;
use app\models\Agents;
use app\assets\AppAsset;
use dosamigos\selectize\SelectizeDropDownList;
use app\config\Constants;

$noticeWayMaps = Constants::NOTICE_WAY_MAPS;
foreach($noticeWayMaps as &$val) {
    $val = Yii::t('app', $val);
}

/* @var $this yii\web\View */
/* @var $model app\models\Crontab */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="crontab-form">
    <div class="row">
        <div class="col-md-8">
            <!-- Horizontal Form -->
            <div class="box box-info table-responsive">
                <div class="box-header with-border">
                    <h3 class="box-title"><?=$this->title?></h3>
                </div>
                <!-- /.box-header -->
                <!-- form start -->
                <?php $form = ActiveForm::begin([
                    'options'=> [
                        'class' => 'form-horizontal smart-form',
                    ],
                    'fieldClass' => 'app\widgets\ActiveField',
                ]); ?>
                <div class="box-body">
                    <?= $form->field($model, 'cid')->widget(SelectizeDropDownList::class, [
                        'items' => Category::getDropDownListData(),
                        'options' => [
                            'style'=>'display:none;',
                            'prompt'=>Yii::t('app', 'Please select'),
                        ],
                    ]);
                    ?>

                    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

                    <?= $form->field($model, 'desc')->textarea(['rows' => '6', 'maxlength' => true, 'placeholder'=>Yii::t('app', 'Please enter the crontab desc')]) ?>

                    <div class="nav-tabs-custom"x>
                        <ul class="nav nav-tabs">
                            <li><a href="#tabs_1" data-toggle="tab">秒</a></li>
                            <li class="active"><a href="#tabs_2" data-toggle="tab">分钟</a></li>
                            <li><a href="#tabs_3" data-toggle="tab">小时</a></li>
                            <li><a href="#tabs_4" data-toggle="tab">日</a></li>
                            <li><a href="#tabs_5" data-toggle="tab">月</a></li>
                            <li><a href="#tabs_6" data-toggle="tab">周</a></li>
                        </ul>
                        <div class="tab-content">
                            <!-- 秒 -->
                            <div id="tabs_1" class="tab-pane">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="radio">
                                            <label class="state-success">
                                                <input type="radio" name="v_second" value="1" class="minimal v_second" />
                                                <i></i>每秒 允许的通配符[, - * /]
                                            </label>
                                        </div>
                                        <div class="radio">
                                            <label class="state-success">
                                                <input type="radio" name="v_second" value="2" class="minimal v_second" />
                                                <i></i>周期,从第<span id="v_secondX_0">X</span>秒到第<span id="v_secondY_0">Y</span>秒
                                            </label>
                                        </div>
                                        <div class="row rule-child-form-control">
                                            <div class="col-md-2">
                                                <input class="form-control spinner-left spinner" id="v_secondStart_0" value="0" type="text" />
                                            </div>
                                            <div class="col-md-1">
                                            </div>
                                            <div class="col-md-2">
                                                <input class="form-control spinner-left spinner" id="v_secondEnd_0" value="1" type="text" />
                                            </div>
                                        </div>
                                        <div class="radio">
                                            <label class="state-success">
                                                <input type="radio" name="v_second" value="3" class="minimal v_second" />
                                                <i></i>从<span id="v_secondX_1">X</span>秒开始,到<span id="v_secondY_1">Y</span>,每<span id="v_secondZ_1">Z</span>秒执行一次
                                            </label>
                                        </div>
                                        <div class="row rule-child-form-control">
                                            <div class="col-md-2">
                                                <input class="form-control spinner-left spinner" id="v_secondStart_1" value="0" type="text">
                                            </div>
                                            <div class="col-md-1">
                                            </div>
                                            <div class="col-md-2">
                                                <input class="form-control spinner-left spinner" id="v_secondEnd_1" value="59" type="text">
                                            </div>
                                            <div class="col-md-1">
                                            </div>
                                            <div class="col-md-2">
                                                <input class="form-control spinner-left spinner" id="v_secondLoop_1" value="1" type="text">
                                            </div>
                                        </div>
                                        <div class="radio">
                                            <label class="state-success">
                                                <input type="radio" name="v_second" value="4" class="minimal v_second" />
                                                <i></i>勾选具体值
                                            </label>
                                        </div>
                                        <?php $n=0; for($i=0;$i<6;$i++){?>
                                            <div class="row rule-child-form-control">
                                                <div class="col-md-12">
                                                    <?php for($j=0;$j<10 and $n<60;$j++){?>
                                                        <label class="checkbox-inline v_secondList">
                                                            <input name="v_secondCheckbox" type="checkbox" data-for="v_second" class="minimal v_secondCheckbox" value="<?=$n?>">
                                                            <span style="margin-left:0;"> <?=$n?> </span>
                                                        </label>
                                                        <?php $n++; }?>
                                                </div>
                                            </div>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                            <!-- 分钟 -->
                            <div id="tabs_2" class="tab-pane active">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="radio">
                                            <label class="state-success">
                                                <input type="radio" name="v_min" value="1" class="minimal v_min" />
                                                <i></i>每分钟 允许的通配符[, - * /]
                                            </label>
                                        </div>
                                        <div class="radio">
                                            <label class="state-success">
                                                <input type="radio" name="v_min" value="2" class="minimal v_min" />
                                                <i></i>周期,从第<span id="v_minX_0">X</span>分钟到第<span id="v_minY_0">Y</span>分钟
                                            </label>
                                        </div>
                                        <div class="row rule-child-form-control">
                                            <div class="col-md-2">
                                                <input class="form-control spinner-left spinner" id="v_minStart_0" type="text" value="0" />
                                            </div>
                                            <div class="col-md-1">
                                            </div>
                                            <div class="col-md-2">
                                                <input class="form-control spinner-left spinner" id="v_minEnd_0"  type="text" value="1" />
                                            </div>
                                        </div>
                                        <div class="radio">
                                            <label class="state-success">
                                                <input type="radio" name="v_min" value="3" class="minimal v_min" />
                                                <i></i>从<span id="v_minX_1">X</span>分钟开始,到<span id="v_minY_1">Y</span>分钟,每<span id="v_minZ_1">Z</span>执行一次
                                            </label>
                                        </div>
                                        <div class="row rule-child-form-control">
                                            <div class="col-md-2">
                                                <input class="form-control spinner-left spinner" id="v_minStart_1" type="text" value="0" />
                                            </div>
                                            <div class="col-md-1">
                                            </div>
                                            <div class="col-md-2">
                                                <input class="form-control spinner-left spinner" id="v_minEnd_1" type="text" value="59" />
                                            </div>
                                            <div class="col-md-1">
                                            </div>
                                            <div class="col-md-2">
                                                <input class="form-control spinner-left spinner" id="v_minLoop_1" value="1" type="text" />
                                            </div>
                                        </div>
                                        <div class="radio">
                                            <label class="state-success">
                                                <input type="radio" name="v_min" value="4" class="minimal v_min" />
                                                <i></i>勾选具体值
                                            </label>
                                        </div>
                                        <?php $n=0; for($i=0;$i<6;$i++){?>
                                            <div class="row rule-child-form-control">
                                                <div class="col-md-12">
                                                    <?php for($j=0;$j<10;$j++){?>
                                                        <label class="checkbox-inline v_minList">
                                                            <input name="v_minCheckbox" type="checkbox" data-for="v_min" class="minimal v_minCheckbox" value="<?=$n?>" />
                                                            <span style="margin-left:0px"> <?=$n?> </span>
                                                        </label>
                                                        <?php $n++; }?>
                                                </div>
                                            </div>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                            <!-- 小时 -->
                            <div id="tabs_3" class="tab-pane">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="radio">
                                            <label class="state-success">
                                                <input type="radio" name="v_hour" value="1" class="minimal v_hour" />
                                                <i></i>每小时 允许的通配符[, - * /]
                                            </label>
                                        </div>
                                        <div class="radio">
                                            <label class="state-success">
                                                <input type="radio" name="v_hour" value="2" class="minimal v_hour" />
                                                <i></i>周期,从第<span id="v_hourX_0">X</span>小时到第<span id="v_hourY_0">Y</span>小时
                                            </label>
                                        </div>
                                        <div class="row rule-child-form-control">
                                            <div class="col-md-2">
                                                <input class="form-control spinner-left spinner" id="v_hourStart_0" type="text" value="1">
                                            </div>
                                            <div class="col-md-1">
                                            </div>
                                            <div class="col-md-2">
                                                <input class="form-control spinner-left spinner" id="v_hourEnd_0" type="text" value="2">
                                            </div>
                                        </div>
                                        <div class="radio">
                                            <label class="state-success">
                                                <input type="radio" name="v_hour" value="3" class="minimal v_hour" />
                                                <i></i>从<span id="v_hourX_1">X</span>小时开始,到<span id="v_hourY_1">Y</span>小时,每<span id="v_hourZ_1">Z</span>小时执行一次
                                            </label>
                                        </div>
                                        <div class="row rule-child-form-control">
                                            <div class="col-md-2">
                                                <input class="form-control spinner-left spinner" id="v_hourStart_1" type="text" value="1">
                                            </div>
                                            <div class="col-md-1">
                                            </div>
                                            <div class="col-md-2">
                                                <input class="form-control spinner-left spinner" id="v_hourEnd_1" type="text" value="23">
                                            </div>
                                            <div class="col-md-1">
                                            </div>
                                            <div class="col-md-2">
                                                <input class="form-control spinner-left spinner" id="v_hourLoop_1" value="1" type="text">
                                            </div>
                                        </div>
                                        <div class="radio">
                                            <label class="state-success">
                                                <input type="radio" name="v_hour" value="4" class="minimal v_hour" />
                                                <i></i>勾选具体值
                                            </label>
                                        </div>
                                        <?php $n=0; for($i=0;$i<2;$i++){?>
                                            <div class="row rule-child-form-control">
                                                <div class="col-md-12">
                                                    <?php for($j=0;$j<12;$j++){?>
                                                        <label class="checkbox-inline v_hourList">
                                                            <input name="v_hourCheckbox" type="checkbox" data-for="v_hour" class="minimal v_hourCheckbox" value="<?=$n?>" />
                                                            <span style="margin-left:0px"> <?=$n?> </span>
                                                        </label>
                                                        <?php $n++; }?>
                                                </div>
                                            </div>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                            <!-- 日 -->
                            <div id="tabs_4" class="tab-pane">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="radio">
                                            <label class="state-success">
                                                <input type="radio" name="v_day" value="1" class="minimal v_day" />
                                                <i></i>日 允许的通配符[, - * /]
                                            </label>
                                        </div>
                                        <div class="radio">
                                            <label class="state-success">
                                                <input type="radio" name="v_day" value="2" class="minimal v_day" />
                                                <i></i>周期,从第<span id="v_dayX_0">X</span>天到第<span id="v_dayY_0">Y</span>天
                                            </label>
                                        </div>
                                        <div class="row rule-child-form-control">
                                            <div class="col-md-2">
                                                <input class="form-control spinner-left spinner" id="v_dayStart_0" type="text" value="1">
                                            </div>
                                            <div class="col-md-1">
                                            </div>
                                            <div class="col-md-2">
                                                <input class="form-control spinner-left spinner" id="v_dayEnd_0" type="text" value="2">
                                            </div>
                                        </div>
                                        <div class="radio">
                                            <label class="state-success">
                                                <input type="radio" name="v_day" value="3" class="minimal v_day" />
                                                <i></i>从<span id="v_dayX_1">X</span>天开始,到<span id="v_dayY_1">Y</span>天,每<span id="v_dayZ_1">Z</span>天执行一次
                                            </label>
                                        </div>
                                        <div class="row rule-child-form-control">
                                            <div class="col-md-2">
                                                <input class="form-control spinner-left spinner" id="v_dayStart_1" type="text" value="1">
                                            </div>
                                            <div class="col-md-1">
                                            </div>
                                            <div class="col-md-2">
                                                <input class="form-control spinner-left spinner"  id="v_dayEnd_1" type="text" value="31">
                                            </div>
                                            <div class="col-md-1">
                                            </div>
                                            <div class="col-md-2">
                                                <input class="form-control spinner-left spinner" id="v_dayLoop_1" value="1" type="text">
                                            </div>
                                        </div>
                                        <div class="radio">
                                            <label class="state-success">
                                                <input type="radio" name="v_day" value="4" class="minimal v_day" />
                                                <i></i>勾选具体值
                                            </label>
                                        </div>
                                        <?php $n=1; for($i=0;$i<3;$i++){?>
                                            <div class="row rule-child-form-control">
                                                <div class="col-md-12">
                                                    <?php for($j=0;$j<11;$j++){ if ($n >31){break;}?>
                                                        <label class="checkbox-inline v_dayList">
                                                            <input name="v_dayCheckbox" type="checkbox" data-for="v_day" class="minimal v_dayCheckbox" value="<?=$n?>" />
                                                            <span style="margin-left:0px"> <?=$n?> </span>
                                                        </label>
                                                        <?php $n++; }?>
                                                </div>
                                            </div>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                            <!-- 月 -->
                            <div id="tabs_5" class="tab-pane">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="radio">
                                            <label class="state-success">
                                                <input type="radio" name="v_mon" value="1" class="minimal v_mon" />
                                                <i></i>月 允许的通配符[, - * /]
                                            </label>
                                        </div>
                                        <div class="radio">
                                            <label class="state-success">
                                                <input type="radio" name="v_mon" value="2" class="minimal v_mon" />
                                                <i></i>周期,从第<span id="v_monX_0">X</span>月到第<span id="v_monY_0">Y</span>月
                                            </label>
                                        </div>
                                        <div class="row rule-child-form-control">
                                            <div class="col-md-2">
                                                <input class="form-control spinner-left spinner" id="v_monStart_0" type="text" value="1">
                                            </div>
                                            <div class="col-md-1">
                                            </div>
                                            <div class="col-md-2">
                                                <input class="form-control spinner-left spinner" id="v_monEnd_0" type="text" value="2">
                                            </div>
                                        </div>
                                        <div class="radio">
                                            <label class="state-success">
                                                <input type="radio" name="v_mon" value="3" class="minimal v_mon" />
                                                <i></i>从<span id="v_monX_1">X</span>月开始,到<span id="v_monY_1">Y</span>月,每<span id="v_monZ_1">Z</span>月执行一次
                                            </label>
                                        </div>
                                        <div class="row rule-child-form-control">
                                            <div class="col-md-2">
                                                <input class="form-control spinner-left spinner" id="v_monStart_1" type="text" value="1">
                                            </div>
                                            <div class="col-md-1">
                                            </div>
                                            <div class="col-md-2">
                                                <input class="form-control spinner-left spinner" id="v_monEnd_1" type="text" value="12">
                                            </div>
                                            <div class="col-md-1">
                                            </div>
                                            <div class="col-md-2">
                                                <input class="form-control spinner-left spinner" id="v_monLoop_1" value="1" type="text">
                                            </div>
                                        </div>
                                        <div class="radio">
                                            <label class="state-success">
                                                <input type="radio" name="v_mon" value="4" class="minimal v_mon" />
                                                <i></i>勾选具体值
                                            </label>
                                        </div>
                                        <?php $n=1; for($i=0;$i<1;$i++){?>
                                            <div class="row rule-child-form-control">
                                                <div class="col-md-12">
                                                    <?php for($j=0;$j<12;$j++){?>
                                                        <label class="checkbox-inline v_monList">
                                                            <input name="v_monCheckbox" type="checkbox" data-for="v_mon" class="minimal v_monCheckbox" value="<?=$n?>" />
                                                            <span style="margin-left:0px"> <?=$n?> </span>
                                                        </label>
                                                        <?php $n++; }?>
                                                </div>
                                            </div>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                            <!-- 周 -->
                            <div id="tabs_6" class="tab-pane">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="radio">
                                            <label class="state-success">
                                                <input type="radio" name="v_week" value="1" class="minimal v_week" />
                                                <i></i>周 允许的通配符[, - * /]
                                            </label>
                                        </div>
                                        <div class="radio">
                                            <label class="state-success">
                                                <input type="radio" name="v_week" value="2" class="minimal v_week" />
                                                <i></i>周期,从星期<span id="v_weekX_0">X</span>到星期<span id="v_weekY_0">Y</span>
                                            </label>
                                        </div>
                                        <div class="row rule-child-form-control">
                                            <div class="col-md-2">
                                                <input class="form-control spinner-left spinner" id="v_weekStart_0" type="text" value="1" />
                                            </div>
                                            <div class="col-md-1">
                                            </div>
                                            <div class="col-md-2">
                                                <input class="form-control spinner-left spinner" id="v_weekEnd_0" type="text" value="2" />
                                            </div>
                                        </div>
                                        <div class="radio">
                                            <label class="state-success">
                                                <input type="radio" name="v_week" value="4" class="minimal v_week" />
                                                <i></i>勾选具体值
                                            </label>
                                        </div>
                                        <?php $n=1; for($i=0;$i<1;$i++){?>
                                            <div class="row rule-child-form-control">
                                                <div class="col-md-12">
                                                    <?php for($j=0;$j<7;$j++){?>
                                                        <label class="checkbox-inline v_weekList">
                                                            <input name="v_weekCheckbox" type="checkbox" data-for="v_week" class="minimal v_weekCheckbox" value="<?=$n?>" />
                                                            <span style="margin-left:0px"> <?=$n?> </span>
                                                        </label>
                                                        <?php $n++; }?>
                                                </div>
                                            </div>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?= $form->field($model, 'rule')->textarea(['rows' => '6', 'maxlength' => true, 'placeholder'=>Yii::t('app', 'Please enter the crontab rule')]) ?>

                    <?= $form->field($model, 'command')->textarea(['rows' => '6', 'maxlength' => true, 'placeholder' => Yii::t('app', 'Please enter the command to execute, must use absolute path.')])->hint('eg: /usr/bin/php /data/path/cli.php crontab test "params"<br />no support: > | >> /path/to/log 2>&1') ?>

                    <?= $form->field($model, 'concurrency')->textInput(['placeholder'=>Yii::t('app', 'Please enter the concurrency number')])->hint(Yii::t('app', 'Zero means no restriction')) ?>

                    <?= $form->field($model, 'max_process_time')->textInput(['placeholder'=>Yii::t('app', 'Please enter the max process time')])->hint(Yii::t('app', 'Zero means no restriction').', '.Yii::t('app', 'The unit is seconds')) ?>

                    <?= $form->field($model, 'timeout_opt')->widget(SelectizeDropDownList::class, [
                        'items' => [
                            Crontab::TIME_OUT_OPT_IGNORE=>Yii::t('app','Ignore'),
                            Crontab::TIME_OUT_OPT_KILL=>Yii::t('app','Kill'),
                        ],
                        'options' => [
                            'placeholder'=>Yii::t('app', 'The default ignore')
                        ],
                    ])?>

                    <?= $form->field($model, 'retries')->textInput(['placeholder'=>Yii::t('app', 'Please enter the number of retries')])->hint(Yii::t('app', 'Zero means no retry')) ?>

                    <?= $form->field($model, 'retry_interval')->textInput(['placeholder'=>Yii::t('app', 'Please enter the retry interval')])->hint(Yii::t('app', 'Zero means immediate retry').', '.Yii::t('app', 'The unit is seconds')) ?>

                    <?= $form->field($model, 'run_user')->textInput(['maxlength' => true, 'placeholder'=>Yii::t('app', 'Please input what user to run the command?')])->hint(Yii::t('app', 'Default: root. Please note that other users may not have log permissions.')) ?>

                    <?= $form->field($model, 'ownerId')->widget(SelectizeDropDownList::class, [
                        'items' => User::getDropDownListData(),
                        'options' => [
                            'multiple'=>'multiple',
                            'style'=>'display:none;',
                        ],
                        'clientOptions' => [
                            'plugins'=> ['remove_button'],
                        ],
                    ])
                    ?>

                    <?= $form->field($model, 'agentId')->widget(SelectizeDropDownList::class, [
                        'items' => Agents::getDropDownListData(),
                        'options' => [
                            'multiple'=>'multiple',
                            'style'=>'display:none;',
                            'placeholder'=>Yii::t('app', 'Default all servers can be executed')
                        ],
                        'clientOptions' => [
                            'plugins'=> ['remove_button'],
                        ],
                    ])
                    ?>

                    <!-- 不在这些节点上运行 -->
                    <?= $form->field($model, 'notInAgentId')->widget(SelectizeDropDownList::class, [
                        'items' => Agents::getDropDownListData(),
                        'options' => [
                            'multiple'=>'multiple',
                            'style'=>'display:none;',
                            'placeholder'=>Yii::t('app', '')
                        ],
                        'clientOptions' => [
                            'plugins'=> ['remove_button'],
                        ],
                    ])
                    ?>

                    <?= $form->field($model, 'notice_way')->widget(SelectizeDropDownList::class, [
                        'items' => $noticeWayMaps,
                        'options' => [
                            'placeholder'=>Yii::t('app', 'Use email notification by default')
                        ],
                    ])
                    ?>

                    <?= $form->field($model, 'status')->dropDownList([
                        Crontab::STATUS_ENABLED=>Yii::t('app','Enabled'),
                        Crontab::STATUS_DISABLED=>Yii::t('app','Disabled'),
                    ])?>
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
<?php
AppAsset::addScript($this, '/js/php.js');
AppAsset::addScript($this, '/js/crontab.js');
?>

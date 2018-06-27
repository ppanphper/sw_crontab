<?php

use yii\helpers\Html;
use yii\grid\GridView;
use app\models\Crontab;
use app\models\Category;
use app\models\User;
use app\models\Agents;
use dosamigos\selectize\SelectizeDropDownList;
use app\helpers\StringHelper;
use app\config\Constants;
use app\assets\AppAsset;

/* @var $this yii\web\View */
/* @var $searchModel app\models\searchs\Crontab */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Crontabs');
$this->params['breadcrumbs'][] = $this->title;
//$noticeWayMaps = Constants::NOTICE_WAY_MAPS;
//foreach ($noticeWayMaps as &$val) {
//	$val = Yii::t('app', $val);
//}
$categoryAllData = Category::getAllData();
?>
<div class="crontab-index">
    <h1><?= $this->title ?></h1>
    <p>
        <?= Html::a(Yii::t('app', 'Create Crontab'), ['create'], ['class' => 'btn btn-success']) ?>
    </p>
    <div class="row">
        <div class="col-xs-12">
            <div class="box table-responsive">
                <!-- /.box-header -->
                <div class="box-body">
                    <?= GridView::widget([
                        'dataProvider' => $dataProvider,
                        'filterModel'  => $searchModel,
                        'columns'      => [
                            [
                                'attribute'     => 'id',
                                'format'        => ['html', ['Attr.AllowedFrameTargets' => ['_blank']]],
                                'value'         => function ($model) {
                                    return Html::a($model->id, ['logs/index', 'Logs[task_id]' => $model->id], ['target' => '_blank', 'title' => Yii::t('app', 'View the run log')]);
                                },
                                'headerOptions' => [
                                    'class' => 'table-crontab-id',
                                ],
                            ],
                            // 分类名称
                            [
                                'format'    => 'html',
                                'filter'    => $categoryAllData,
                                'value'     => function ($model) use ($categoryAllData) {
                                    if ($model->cid && isset($categoryAllData[$model->cid])) {
                                        return Html::a($categoryAllData[$model->cid], ['index', 'Crontab[cid]' => $model->cid], ['class' => 'category-link']);
                                    }
                                    return '<span class="not-set">' . Yii::t('yii', '(not set)') . '</span>';
                                },
                                'attribute' => 'cid',
                            ],
                            // 任务名称
                            [
                                'format'    => 'html',
                                'value'     => function ($model) {
                                    return Html::a($model->name, ['view', 'id'=>$model->id], ['class' => 'view-link']);
                                },
                                'attribute' => 'name',
                            ],
                            // 任务执行规则
                            [
                                'attribute' => 'rule',
                                'value'     => function ($model) {
                                    return StringHelper::cutSub($model->rule, 30);
                                },
                            ],

                            // 并发数
//                            [
//                                'attribute'     => 'concurrency', // 字段名
//                                'value'         => function ($model) { // 该列内容
//                                    $content = $model->concurrency;
//                                    if ($model->concurrency == 0) {
//                                        $content = '不限制';
//                                    }
//                                    return $content;
//                                },
//                                'headerOptions' => [
//                                    'class' => 'table-crontab-concurrency',
//                                ],
//                            ],
//                            // 最大执行时间
//                            [
//                                'attribute'     => 'max_process_time', // 字段名
//                                'value'         => function ($model) { // 该列内容
//                                    $content = $model->max_process_time . '秒';
//                                    if ($model->max_process_time == 0) {
//                                        $content = '不限制';
//                                    }
//                                    return $content;
//                                },
//                                'headerOptions' => [
//                                    'class' => 'table-crontab-max_process_time',
//                                ],
//                            ],
                            // 命令
                            [
                                'attribute'     => 'command',
                                'headerOptions' => [
                                    'class' => 'table-crontab-command',
                                ],
                            ],
//                            // 运行时用户
//                            [
//                                'attribute'     => 'run_user',
//                                'headerOptions' => [
//                                    'class' => 'table-crontab-run_user',
//                                ],
//                            ],
//                            // 负责人
//                            [
//                                'attribute'     => 'owner',
//                                'format'        => 'html',
//                                'filter'        => SelectizeDropDownList::widget([
//                                    'model'     => $searchModel,
//                                    'attribute' => 'owner',
//                                    'items'     => User::getDropDownListData(),
//                                    'options'   => [
//                                        'prompt' => Yii::t('app', 'Please select'),
//                                    ],
//                                ]),
//                                'value'         => function ($model) {
//                                    $html = '';
//                                    if ($model->owner && ($data = User::getDropDownListData($model->owner))) {
//                                        foreach ($data as $id => $name) {
//                                            $html .= '<span class="label label-info">' . html::encode($name) . '</span>&nbsp;';
//                                        }
//                                    }
//                                    return $html;
//                                },
//                                'headerOptions' => [
//                                    'class' => 'table-crontab-owner',
//                                ],
//                            ],
//                            // 服务器
//                            [
//                                'attribute' => 'agents',
//                                'format'    => 'html',
//                                'filter'    => SelectizeDropDownList::widget([
//                                    'model'         => $searchModel,
//                                    'attribute'     => 'agents',
//                                    'items'         => Agents::getDropDownListData(),
//                                    'options'       => [
//                                        'style'  => 'display:none;',
//                                        'prompt' => Yii::t('app', 'Please select'),
//                                    ],
//                                    'clientOptions' => [
//                                        'valueField'  => 'id',
//                                        'labelField'  => 'name',
//                                        'searchField' => ['name'],
//                                    ],
//                                ]),
//                                'label'     => Yii::t('app', 'Agents'),
//                                'value'     => function ($model) {
//                                    $html = '<ul class="list-unstyled">';
//                                    if ($model->agents && ($data = Agents::getDropDownListData($model->agents))) {
//                                        foreach ($data as $id => $name) {
//                                            $html .= '<li><span class="label label-info">' . html::encode($name) . '</span></li>';
//                                        }
//                                    }
//                                    $html .= '</ul>';
//                                    return $html;
//                                },
//                            ],
//                            [
//                                'format'    => 'html', // 此列内容输出时不会被转义
//                                'filter'    => $noticeWayMaps,
//                                'attribute' => 'notice_way', // 字段名
//                                'value'     => function ($model) use ($noticeWayMaps) { // 该列内容
//                                    $html = '<ul class="list-unstyled">';
//                                    if ($model->notice_way) {
//                                        $label = $noticeWayMaps[$model->notice_way];
//                                        if (empty($label)) {
//                                            $label = 'Unknown way';
//                                        }
//                                        $html .= '<li><span class="label label-info">' . Yii::t('app', $label) . '</span></li>';
//                                    }
//                                    $html .= '</ul>';
//                                    return $html;
//                                },
//                            ],
                            // 状态
                            [
                                'format'    => 'raw', // 此列内容输出时不会被转义
                                'filter'    => [ // 过滤器，也就是搜索框。该值为数组时会显示一个下拉框（dropdown list）
                                    Crontab::STATUS_DISABLED => Yii::t('app', 'Disabled'),
                                    Crontab::STATUS_ENABLED  => Yii::t('app', 'Enabled'),
                                ],
                                'attribute' => 'status', // 字段名
                                'value'     => function ($model) { // 该列内容
                                    if ($model->status == Crontab::STATUS_DISABLED) return '<span class="label label-danger crontab-btn-status" data-value="0">' . Yii::t('app', 'Disabled') . '</span>';
                                    if ($model->status == Crontab::STATUS_ENABLED) return '<span class="label label-success crontab-btn-status" data-value="1">' . Yii::t('app', 'Enabled') . '</span>';
                                },
                            ],
                            [
                                'class'    => 'yii\grid\ActionColumn',
                                'template' => '<div class="white-space-normal">{copy} {view} {update} {delete}</div>',
                                'buttons' => [
                                    'copy' => function ($url, $model, $key) {
                                        $title = Yii::t('app', 'Copy');
                                        $options = [
                                            'title' => $title,
                                            'aria-label' => $title,
                                            'data-pjax' => '0',
                                        ];
                                        $icon = Html::tag('span', '', ['class' => "fa fa-copy"]);
                                        return Html::a($icon, $url, $options);
                                    },
                                ],
                            ],
                        ],
                    ]); ?>
                </div>
                <!-- /.box-body -->
            </div>
            <!-- /.box -->
        </div>
        <!-- /.col -->
    </div>
    <!-- /.row -->
</div>

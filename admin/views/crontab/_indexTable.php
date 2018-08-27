<?php
/**
 * Created by PhpStorm.
 * User: pandy
 * Date: 2018/7/3
 * Time: 下午5:42
 */
use yii\grid\GridView;

use yii\helpers\Html;
use app\models\Crontab;
use app\models\Category;
use dosamigos\selectize\SelectizeDropDownList;
use app\helpers\StringHelper;
use app\config\Constants;
use app\models\Agents;
//$rows = Agents::getData();
//$agentsData = [];
//if($rows) {
//	foreach($rows as $row) {
//		$agentsData[$row['id']] = $row['name'].'('.$row['ip'].')';
//	}
//}
//unset($rows, $row);
?>
<div class="box table-responsive box-info">
    <!-- /.box-header -->
    <div class="box-body">
        <?= GridView::widget([
            'dataProvider' => $dataProvider,
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

//				// 并发数
//				[
//					'attribute'     => 'concurrency', // 字段名
//					'value'         => function ($model) { // 该列内容
//						$content = $model->concurrency;
//						if ($model->concurrency == 0) {
//							$content = '不限制';
//						}
//						return $content;
//					},
//					'headerOptions' => [
//						'class' => 'table-crontab-concurrency',
//					],
//				],
//				// 最大执行时间
//				[
//					'attribute'     => 'max_process_time', // 字段名
//					'value'         => function ($model) { // 该列内容
//						$content = $model->max_process_time . '秒';
//						if ($model->max_process_time == 0) {
//							$content = '不限制';
//						}
//						return $content;
//					},
//					'headerOptions' => [
//						'class' => 'table-crontab-max_process_time',
//					],
//				],
                // 命令
                [
                    'attribute'     => 'command',
                    'headerOptions' => [
                        'class' => 'table-crontab-command',
                    ],
                ],
                // 运行时用户
//				[
//					'attribute'     => 'run_user',
//					'headerOptions' => [
//						'class' => 'table-crontab-run_user',
//					],
//				],
//				// 负责人
//				[
//					'attribute'     => 'owner',
//					'format'        => 'html',
//					'filter'        => SelectizeDropDownList::widget([
//						'model'     => $searchModel,
//						'attribute' => 'owner',
//						'items'     => User::getDropDownListData(),
//						'options'   => [
//							'prompt' => Yii::t('app', 'Please select'),
//						],
//					]),
//					'value'         => function ($model) {
//						$html = '';
//						if ($model->owner && ($data = User::getDropDownListData($model->owner))) {
//							foreach ($data as $id => $name) {
//								$html .= '<span class="label label-info">' . html::encode($name) . '</span>&nbsp;';
//							}
//						}
//						return $html;
//					},
//					'headerOptions' => [
//						'class' => 'table-crontab-owner',
//					],
//				],
//				/**
//				 * 节点，如果要显示任务在哪些节点运行，需要把CrontabSearchModel的Search方法中JoinWith放到外面
//				 * 还需要判断此任务是否在这些节点上运行
//				 */
//				[
//					'attribute' => 'agentId',
//					'format'    => 'html',
//					'label'     => Yii::t('app', 'Agents'),
//					'value'     => function ($model) use($agentsData) {
//						$html = '<ul class="list-unstyled">';
//						if ($model->agents) {
//							foreach ($model->agents as $agent) {
//								if(isset($agentsData[$agent->bid])) {
//									$html .= '<li><span class="label label-primary">' . html::encode($agentsData[$agent->bid]) . '</span></li>';
//								}
//							}
//						}
//						else {
//							$html .= '<li><span class="label label-warning">全部节点</span></li>';
//                        }
//						$html .= '</ul>';
//						return $html;
//					},
//				],
//				[
//					'format'    => 'html', // 此列内容输出时不会被转义
//					'filter'    => $noticeWayMaps,
//					'attribute' => 'notice_way', // 字段名
//					'value'     => function ($model) use ($noticeWayMaps) { // 该列内容
//						$html = '<ul class="list-unstyled">';
//						if ($model->notice_way) {
//							$label = $noticeWayMaps[$model->notice_way];
//							if (empty($label)) {
//								$label = 'Unknown way';
//							}
//							$html .= '<li><span class="label label-info">' . Yii::t('app', $label) . '</span></li>';
//						}
//						$html .= '</ul>';
//						return $html;
//					},
//				],
                // 状态
                [
                    'format'    => 'raw', // 此列内容输出时不会被转义
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

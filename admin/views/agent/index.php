<?php

use yii\helpers\Html;
use yii\grid\GridView;
use app\models\Agents;
use app\models\Category;

/* @var $this yii\web\View */
/* @var $searchModel app\models\searchs\Agents */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Agents List');
$this->params['breadcrumbs'][] = $this->title;
$categoryAllData = Category::getDropDownListData();
?>
<div class="agents-index">
    <div class="mt-20"></div>
    <div class="row">
        <div class="col-xs-12">
            <?= $this->render('_search', [
                'model' => $searchModel,
                'categoryAllData' => $categoryAllData,
            ]) ?>
            <div class="box box-info table-responsive">
                <!-- /.box-header -->
                <div class="box-body">
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'columns' => [
                        'id',

                        [
                            'format'    => 'html',
                            'attribute' => 'name', // 字段名
                            'value'     => function ($model) {
                                return Html::a($model->name, ['crontab/index', 'Crontab[agentId]' => $model->id], ['title' => Yii::t('app', 'Click to view the list of tasks under the node')]);
                            }
                        ],

                        // 分类名称
                        [
                            'format' => 'html',
                            'value'  => function ($model) use($categoryAllData) {
                                $html = '';
                                if($model->category) {
                                    foreach($model->category as $item) {
                                        if(isset($categoryAllData[$item->bid])) {
                                            $html .= Html::a($categoryAllData[$item->bid], ['index', 'Agents[categoryId]'=>$item->bid], ['class'=>'category-link']);
                                        }
//										$html .= Html::a($item->name, ['index', 'Agents[categoryId]'=>$item->id], ['class'=>'category-link']);
                                    }
                                }
                                if(empty($html)) {
                                    $html = '<span class="not-set">' . Yii::t('yii', '(not set)') . '</span>';
                                }
                                return $html;
                            },
                            'attribute'=> 'categoryId',
                        ],

                        [
                            'format'    => 'html',
                            'attribute' => 'ip', // 字段名
                            'value'     => function ($model) {
                                return Html::a($model->ip, ['crontab/index', 'Crontab[agentId]' => $model->id], ['title' => Yii::t('app', 'Click to view the list of tasks under the node')]);
                            }
                        ],
                        // 端口
                        'port',

                        // 节点状态
                        [
                            'format'    => 'html', // 此列内容输出时不会被转义
                            'attribute' => 'agent_status', // 字段名
                            'value'     => function ($model) { // 该列内容
                                if ($model->agent_status == Agents::AGENT_STATUS_OFFLINE) return '<span class="label label-danger">' . Yii::t('app', 'Offline') . '</span>';
                                if ($model->agent_status == Agents::AGENT_STATUS_ONLINE) return '<span class="label label-success">' . Yii::t('app', 'Normal') . '</span>';
                                if ($model->agent_status == Agents::AGENT_STATUS_ONLINE_REPORT_FAILED) return '<span class="label label-success">' . Yii::t('app', 'No heartbeat, but the nodes are normal') . '</span>';
                            },
                        ],
                        // 心跳
                        [
                            'format' => 'html',
                            'label' => Yii::t('app', 'Heartbeat'),
                            'value' => function ($model) {
                                if($model->last_report_time) {
                                    return Yii::$app->formatter->asDatetime($model->last_report_time);
                                }
                                return '';
                            },
                        ],

                        [
                            'class' => 'yii\grid\ActionColumn',
                            'header' => Yii::t('app', 'Operation'),
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

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
?>
<div class="agents-index">
    <h1><?= $this->title ?></h1>
    <p>
        <?= Html::a(Yii::t('app', 'Create Agents'), ['create'], ['class' => 'btn btn-success']) ?>
    </p>
    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <!-- /.box-header -->
                <div class="box-body table-responsive">
                    <?= GridView::widget([
                        'dataProvider' => $dataProvider,
                        'filterModel'  => $searchModel,
                        'columns'      => [
                            'id',

                            'name',

                            // 分类名称
                            [
                                'format'    => 'html',
                                'filter'    => Category::getAllData(),
                                'value'     => function ($model) {
                                    if ($model->category) {
                                        $html = '';
                                        foreach ($model->category as $item) {
                                            $html .= Html::a($item->name, ['index', 'Agents[categoryId]' => $item->id], ['class' => 'category-link']);
                                        }
                                        return $html;
                                    }
                                    return '<span class="not-set">' . Yii::t('yii', '(not set)') . '</span>';
                                },
                                'attribute' => 'categoryId',
                            ],

                            'ip',

                            // 端口
                            [
                                'format'    => 'html',
                                'attribute' => 'port', // 字段名
                                'value'     => function ($model) {
                                    return Html::a($model->port, ['index', 'Agents[port]' => $model->port]);
                                }
                            ],
                            // 状态
                            [
                                'format'    => 'html', // 此列内容输出时不会被转义
                                'filter'    => [ // 过滤器，也就是搜索框。该值为数组时会显示一个下拉框（dropdown list）
                                    Agents::STATUS_DISABLED => Yii::t('app', 'Disabled'),
                                    Agents::STATUS_ENABLED  => Yii::t('app', 'Enabled'),
                                ],
                                'attribute' => 'status', // 字段名
                                'value'     => function ($model) { // 该列内容
                                    if ($model->status == Agents::STATUS_DISABLED) return '<span class="label label-danger">' . Yii::t('app', 'Disabled') . '</span>';
                                    if ($model->status == Agents::STATUS_ENABLED) return '<span class="label label-success">' . Yii::t('app', 'Enabled') . '</span>';
                                },
                            ],
                            // 心跳
                            [
                                'label' => Yii::t('app', 'Heartbeat'),
                                'value' => function ($model) {
                                    return $model->getHeartbeatTime($model->ip, $model->port);
                                },
                            ],

                            [
                                'class'  => 'yii\grid\ActionColumn',
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

<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Crontab */

$this->title = Yii::t('app', 'Update Crontab') . ': ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Crontabs'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<div class="crontab-update">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>

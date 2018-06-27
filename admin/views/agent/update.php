<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Agents */

$this->title = Yii::t('app', 'Update Agents') . ': ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Agents'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<div class="agents-update">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>

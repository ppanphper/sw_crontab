<?php

/* @var $this yii\web\View */
/* @var $model app\models\Agents */

$this->title = Yii::t('app', 'Create Agents');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Agents'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="agents-create">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>

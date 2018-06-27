<?php

/* @var $this yii\web\View */
/* @var $model app\models\Crontab */

$this->title = Yii::t('app', 'Create Crontab');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Crontabs'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="crontab-create">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>

<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model Ua0leg\Yii2ActionLog\models\ActionLog */

$this->title = 'Create Action Log';
$this->params['breadcrumbs'][] = ['label' => 'Action Log', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="action-log-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', ['model' => $model]) ?>

</div>

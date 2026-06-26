<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model Ua0leg\Yii2ActionLog\models\ActionLogSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="action-log-search">

    <?php $form = ActiveForm::begin([
        'action'  => ['index'],
        'method'  => 'get',
        'options' => ['data-pjax' => 1],
    ]); ?>

    <?= $form->field($model, 'id') ?>
    <?= $form->field($model, 'table_name') ?>
    <?= $form->field($model, 'id_model') ?>
    <?= $form->field($model, 'id_user') ?>
    <?= $form->field($model, 'ipv4') ?>

    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('Reset', ['class' => 'btn btn-outline-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>

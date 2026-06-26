<?php

use Ua0leg\Yii2ActionLog\models\ActionLog;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model Ua0leg\Yii2ActionLog\models\ActionLog */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="action-log-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'table_name')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'id_model')->textInput() ?>
    <?= $form->field($model, 'id_user')->textInput() ?>
    <?= $form->field($model, 'ipv4')->textInput() ?>
    <?= $form->field($model, 'action')->dropDownList(ActionLog::actionsFilter(), ['prompt' => '']) ?>
    <?= $form->field($model, 'before')->textarea(['rows' => 4]) ?>
    <?= $form->field($model, 'after')->textarea(['rows' => 4]) ?>
    <?= $form->field($model, 'created_at')->textInput() ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>

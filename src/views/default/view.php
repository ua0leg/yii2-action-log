<?php

use Ua0leg\Yii2ActionLog\models\ActionLog;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\View;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model Ua0leg\Yii2ActionLog\models\ActionLog */
/* @var $diff array */

$this->title = $model->table_name . ' #' . $model->id_model;
$this->params['breadcrumbs'][] = ['label' => 'Action Log', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="action-log-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= DetailView::widget([
        'model'      => $model,
        'attributes' => [
            'id',
            [
                'attribute' => 'id_model',
                'value'     => $model->id_model ? $model->actionModelLabel : 'not found',
            ],
            [
                'attribute' => 'id_user',
                'value'     => $model->getUserLabel() ?: 'unknown',
            ],
            'created_at:datetime',
            [
                'attribute' => 'ipv4',
                'label'     => 'IP',
                'value'     => $model->ipv4 ? $model->ip : '',
            ],
            [
                'attribute' => 'action',
                'value'     => $model->actionName,
            ],
            'table_name',
            [
                'label'   => $model->getAttributeLabel('before') . ' / ' . $model->getAttributeLabel('after'),
                'format'  => 'raw',
                'visible' => $diff !== [],
                'value'   => $this->render('_diff', ['log' => $model, 'diff' => $diff]),
            ],
            [
                'attribute' => 'before',
                'format'    => 'raw',
                'visible'   => $diff === [] && (bool) $model->before,
                'value'     => '<pre>' . Html::encode(Json::encode(ActionLog::decodeSnapshot($model->before), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>',
            ],
            [
                'attribute' => 'after',
                'format'    => 'raw',
                'visible'   => $diff === [] && (bool) $model->after,
                'value'     => '<pre>' . Html::encode(Json::encode(ActionLog::decodeSnapshot($model->after), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>',
            ],
        ],
    ]) ?>

</div>

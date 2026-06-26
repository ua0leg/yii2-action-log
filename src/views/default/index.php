<?php

use Ua0leg\Yii2ActionLog\models\ActionLog;
use yii\grid\ActionColumn;
use yii\grid\GridView;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $searchModel Ua0leg\Yii2ActionLog\models\ActionLogSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Action Log';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="action-log-index">

    <p>
        <?= Html::a('Statistics', ['stat'], ['class' => 'btn btn-outline-primary btn-sm']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel'  => $searchModel,
        'columns'      => [
            [
                'attribute' => 'id',
                'format'    => 'raw',
                'value'     => static function (ActionLog $model) {
                    return Html::a($model->num, ['view', 'id' => $model->id]);
                },
                'headerOptions' => ['style' => 'width:60px'],
            ],
            'table_name',
            [
                'attribute' => 'id_model',
                'value'     => static function (ActionLog $model) {
                    return $model->id_model ? $model->actionModelLabel : '';
                },
            ],
            [
                'attribute' => 'id_user',
                'value'     => static function (ActionLog $model) {
                    return $model->getUserLabel();
                },
            ],
            [
                'attribute' => 'ipv4',
                'label'     => 'IP',
                'value'     => static function (ActionLog $model) {
                    return $model->ipv4 ? $model->ip : '';
                },
            ],
            [
                'attribute' => 'action',
                'filter'    => ActionLog::actionsFilter(),
                'value'     => static function (ActionLog $model) {
                    return $model->actionName;
                },
            ],
            'created_at:datetime',
            ['class' => ActionColumn::class, 'template' => '{view} {update} {delete}'],
        ],
    ]) ?>

</div>

<?php

use Ua0leg\Yii2ActionLog\models\ActionLog;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $days int */
/* @var $stats array */

$this->title = 'Action Log Statistics';
$this->params['breadcrumbs'][] = ['label' => 'Action Log', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$periods = [
    7   => '7 days',
    30  => '30 days',
    90  => '90 days',
    365 => '1 year',
    0   => 'All time',
];
$maxDayCount = 1;
foreach ($stats['byDay'] as $row) {
    $maxDayCount = max($maxDayCount, $row['count']);
}

$totals = [
    ['label' => 'Total', 'value' => (int) $stats['totals']['all'], 'sub' => $periods[$days] ?? ''],
    ['label' => ActionLog::actionsFilter()[ActionLog::ACTION_CREATE], 'value' => (int) $stats['totals']['create']],
    ['label' => ActionLog::actionsFilter()[ActionLog::ACTION_UPDATE], 'value' => (int) $stats['totals']['update']],
    ['label' => ActionLog::actionsFilter()[ActionLog::ACTION_DELETE], 'value' => (int) $stats['totals']['delete']],
];
?>
<div class="action-log-stat">

    <div class="mb-3">
        <div class="btn-group btn-group-sm" role="group">
            <?php foreach ($periods as $value => $label): ?>
                <?= Html::a($label, ['stat', 'days' => $value], [
                    'class' => 'btn ' . ($days === $value ? 'btn-primary' : 'btn-outline-primary'),
                ]) ?>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="row mb-4">
        <?php foreach ($totals as $card): ?>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h6 class="card-title"><?= Html::encode($card['label']) ?></h6>
                        <?php if (!empty($card['sub'])): ?>
                            <small class="text-muted"><?= Html::encode($card['sub']) ?></small>
                        <?php endif; ?>
                        <p class="display-6 mb-0"><?= Yii::$app->formatter->asInteger($card['value']) ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="row mb-4">
        <div class="col-lg-6 mb-3">
            <div class="card h-100">
                <div class="card-header">Top 5 users</div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>User</th>
                            <th class="text-end">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if ($stats['topUsers'] === []): ?>
                            <tr><td colspan="3" class="text-muted text-center">No data</td></tr>
                        <?php else: ?>
                            <?php foreach ($stats['topUsers'] as $i => $row): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= Html::encode($row['name']) ?></td>
                                    <td class="text-end">
                                        <?= Html::a(
                                            Yii::$app->formatter->asInteger($row['count']),
                                            ['index', 'ActionLogSearch' => ['id_user' => $row['id_user']]]
                                        ) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-3">
            <div class="card h-100">
                <div class="card-header">Top 5 tables</div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Table</th>
                            <th class="text-end">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if ($stats['topTables'] === []): ?>
                            <tr><td colspan="3" class="text-muted text-center">No data</td></tr>
                        <?php else: ?>
                            <?php foreach ($stats['topTables'] as $i => $row): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= Html::encode($row['table_name']) ?></td>
                                    <td class="text-end">
                                        <?= Html::a(
                                            Yii::$app->formatter->asInteger($row['count']),
                                            ['index', 'ActionLogSearch' => ['table_name' => $row['table_name']]]
                                        ) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Activity by day</div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($stats['byDay'] === []): ?>
                    <tr><td colspan="2" class="text-muted text-center">No data</td></tr>
                <?php else: ?>
                    <?php foreach ($stats['byDay'] as $row): ?>
                        <?php $width = round($row['count'] / $maxDayCount * 100); ?>
                        <tr>
                            <td><?= Yii::$app->formatter->asDate($row['date']) ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 10px;">
                                        <div class="progress-bar" style="width: <?= $width ?>%;"></div>
                                    </div>
                                    <span><?= Yii::$app->formatter->asInteger($row['count']) ?></span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

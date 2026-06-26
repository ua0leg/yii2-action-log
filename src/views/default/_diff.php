<?php

use Ua0leg\Yii2ActionLog\models\ActionLog;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;

/* @var $this yii\web\View */
/* @var $log Ua0leg\Yii2ActionLog\models\ActionLog */
/* @var $diff list<array{attribute: string, old: mixed, new: mixed}> */

$canRevert = $log->action === ActionLog::ACTION_UPDATE && $log->actionModel instanceof \yii\db\ActiveRecord;
$revertUrl = Url::to(['revert']);
$csrfParam = Yii::$app->request->csrfParam;
$csrfToken = Yii::$app->request->csrfToken;

$js = <<<JS
(function () {
    var container = document.querySelector('.action-log-diff[data-log-id="{$log->id}"]');
    if (!container) {
        return;
    }

    function notify(success, message) {
        alert(message);
    }

    function setLoading(btn, loading) {
        btn.disabled = loading;
    }

    function sendRevert(direction, attribute, btn) {
        var data = new FormData();
        data.append('log_id', container.dataset.logId);
        data.append('direction', direction);
        data.append('{$csrfParam}', '{$csrfToken}');
        if (attribute) {
            data.append('attribute', attribute);
        }

        setLoading(btn, true);
        fetch(container.dataset.revertUrl, {
            method: 'POST',
            body: data,
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
            .then(function (response) { return response.json(); })
            .then(function (result) {
                notify(result.success === true, result.message || (result.success ? 'Reverted' : 'Revert failed'));
            })
            .catch(function () {
                notify(false, 'Revert failed');
            })
            .finally(function () {
                setLoading(btn, false);
            });
    }

    container.addEventListener('click', function (event) {
        var btn = event.target.closest('[data-direction]');
        if (!btn || btn.disabled) {
            return;
        }
        event.preventDefault();
        sendRevert(btn.dataset.direction, btn.dataset.attribute || null, btn);
    });
})();
JS;
$this->registerJs($js, View::POS_READY);
?>
<div class="action-log-diff" data-log-id="<?= (int) $log->id ?>" data-revert-url="<?= Html::encode($revertUrl) ?>">
    <?php if ($canRevert): ?>
        <div class="mb-2">
            <?= Html::button('Undo all', [
                'type'           => 'button',
                'class'          => 'btn btn-sm btn-outline-secondary me-1',
                'data-direction' => 'undo',
            ]) ?>
            <?= Html::button('Redo all', [
                'type'           => 'button',
                'class'          => 'btn btn-sm btn-outline-secondary',
                'data-direction' => 'redo',
            ]) ?>
        </div>
    <?php endif; ?>
    <table class="table table-sm table-bordered mb-0">
        <thead>
        <tr>
            <th>Attribute</th>
            <th>Before</th>
            <th>After</th>
            <?php if ($canRevert): ?>
                <th class="text-center" style="width: 120px;">Actions</th>
            <?php endif; ?>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($diff as $row): ?>
            <tr>
                <td><?= Html::encode($row['attribute']) ?></td>
                <td><?= nl2br(Html::encode(ActionLog::formatLogValue($row['old']))) ?></td>
                <td><?= nl2br(Html::encode(ActionLog::formatLogValue($row['new']))) ?></td>
                <?php if ($canRevert): ?>
                    <td class="text-center text-nowrap">
                        <?= Html::button('Undo', [
                            'type'           => 'button',
                            'class'          => 'btn btn-sm btn-outline-secondary me-1',
                            'data-direction' => 'undo',
                            'data-attribute' => $row['attribute'],
                        ]) ?>
                        <?= Html::button('Redo', [
                            'type'           => 'button',
                            'class'          => 'btn btn-sm btn-outline-secondary',
                            'data-direction' => 'redo',
                            'data-attribute' => $row['attribute'],
                        ]) ?>
                    </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

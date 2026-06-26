<?php

namespace Ua0leg\Yii2ActionLog\components;

use Ua0leg\Yii2ActionLog\models\ActionLog;
use yii\db\ActiveRecord;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;

class ActionLogRevert
{
    private const BLOCKED_ATTRIBUTES = [
        'id',
        'created_at',
        'updated_at',
        'password',
        'password_hash',
        'auth_key',
    ];

    /**
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     */
    public static function apply(ActionLog $log, string $direction, ?string $attribute = null): void
    {
        if ($log->action !== ActionLog::ACTION_UPDATE) {
            throw new BadRequestHttpException('Only update log entries can be reverted');
        }

        if (!in_array($direction, ['undo', 'redo'], true)) {
            throw new BadRequestHttpException('Invalid revert direction');
        }

        $target = $log->actionModel;
        if (!$target instanceof ActiveRecord) {
            throw new NotFoundHttpException('Record not found');
        }

        $beforeArr = ActionLog::decodeSnapshot($log->before);
        $afterArr = ActionLog::decodeSnapshot($log->after);
        $keys = array_unique(array_merge(array_keys($beforeArr), array_keys($afterArr)));
        sort($keys, SORT_STRING);

        $valuesToApply = [];
        foreach ($keys as $key) {
            if (in_array($key, self::BLOCKED_ATTRIBUTES, true)) {
                continue;
            }
            if ($attribute !== null && $key !== $attribute) {
                continue;
            }

            $old = array_key_exists($key, $beforeArr) ? $beforeArr[$key] : null;
            $new = array_key_exists($key, $afterArr) ? $afterArr[$key] : null;
            if (ActionLog::logValuesEffectivelyEqual($old, $new)) {
                continue;
            }

            $valuesToApply[$key] = $direction === 'undo' ? $old : $new;
        }

        if ($valuesToApply === []) {
            throw new BadRequestHttpException('No attributes to revert');
        }

        if ($attribute !== null && !array_key_exists($attribute, $valuesToApply)) {
            throw new BadRequestHttpException('Attribute cannot be reverted');
        }

        foreach (array_keys($valuesToApply) as $key) {
            if (!$target->hasAttribute($key)) {
                throw new BadRequestHttpException('Unknown attribute: ' . $key);
            }
        }

        $changedBefore = [];
        foreach (array_keys($valuesToApply) as $key) {
            $changedBefore[$key] = $target->getAttribute($key);
        }

        if ($target->updateAttributes($valuesToApply) === false) {
            throw new BadRequestHttpException('Failed to update record');
        }

        ActionLog::add(
            $log->table_name,
            (int) $log->id_model,
            ActionLog::ACTION_UPDATE,
            $changedBefore,
            $target->attributes
        );
    }
}

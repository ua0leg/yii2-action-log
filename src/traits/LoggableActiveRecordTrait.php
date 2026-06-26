<?php

namespace Ua0leg\Yii2ActionLog\traits;

use Ua0leg\Yii2ActionLog\models\ActionLog;

/**
 * Writes ActionLog entries on save and delete. Call from afterSave / beforeDelete.
 */
trait LoggableActiveRecordTrait
{
    protected function logActionLogAfterSave(bool $insert, array $changedAttributes): void
    {
        if ($insert) {
            ActionLog::add(
                static::tableName(),
                (int) $this->id,
                ActionLog::ACTION_CREATE,
                null,
                $this->attributes
            );
            return;
        }

        if ($changedAttributes === []) {
            return;
        }

        ActionLog::add(
            static::tableName(),
            (int) $this->id,
            ActionLog::ACTION_UPDATE,
            $changedAttributes,
            $this->attributes
        );
    }

    protected function logActionLogBeforeDelete(?string $tableName = null): void
    {
        ActionLog::add(
            $tableName ?? static::tableName(),
            (int) $this->id,
            ActionLog::ACTION_DELETE,
            $this->attributes
        );
    }
}

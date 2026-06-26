<?php

namespace Ua0leg\Yii2ActionLog;

use Ua0leg\Yii2ActionLog\traits\LoggableActiveRecordTrait;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\BaseActiveRecord;

/**
 * ActiveRecord with timestamps, blameable user ids, and ActionLog on save/delete.
 *
 * Table must have: created_at, updated_at, created_by, updated_by.
 */
abstract class LoggableActiveRecord extends ActiveRecord
{
    use LoggableActiveRecordTrait;

    public function behaviors(): array
    {
        return [
            'timestamp' => [
                'class'      => TimestampBehavior::class,
                'attributes' => [
                    BaseActiveRecord::EVENT_BEFORE_INSERT => ['created_at'],
                    BaseActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
                ],
                'value'      => static function () {
                    return gmdate('Y-m-d H:i:s');
                },
            ],
            'blameable' => [
                'class'              => BlameableBehavior::class,
                'createdByAttribute' => 'created_by',
                'updatedByAttribute' => 'updated_by',
            ],
        ];
    }

    public function afterSave($insert, $changedAttributes): void
    {
        parent::afterSave($insert, $changedAttributes);
        $this->logActionLogAfterSave($insert, $changedAttributes);
    }

    public function beforeDelete(): bool
    {
        if (!parent::beforeDelete()) {
            return false;
        }

        $this->logActionLogBeforeDelete();

        return true;
    }
}

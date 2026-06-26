<?php

use yii\db\Migration;

class m240128_181452_action_log extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%action_log}}', [
            'id'         => $this->primaryKey(11)->unsigned(),
            'table_name' => $this->string(64)->null()->defaultValue(null),
            'id_model'   => $this->integer(11)->unsigned()->null()->defaultValue(null),
            'id_user'    => $this->integer(11)->unsigned()->null()->defaultValue(null),
            'ipv4'       => $this->integer(10)->unsigned()->null()->defaultValue(null),
            'action'     => "enum('create', 'view', 'update', 'delete', 'export', 'print') NULL DEFAULT NULL",
            'before'     => $this->text()->null()->defaultValue(null),
            'after'      => $this->text()->null()->defaultValue(null),
            'created_at' => $this->timestamp()->null()->defaultValue(null),
        ]);

        $this->createIndex('idx-action_log-id_model', '{{%action_log}}', 'id_model');
        $this->createIndex('idx-action_log-id_user', '{{%action_log}}', 'id_user');
        $this->createIndex('idx-action_log-created_at', '{{%action_log}}', 'created_at');
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%action_log}}');
    }
}

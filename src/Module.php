<?php

namespace Ua0leg\Yii2ActionLog;

use Ua0leg\Yii2ActionLog\models\ActionLog;
use yii\base\Module as BaseModule;

class Module extends BaseModule
{
    public $controllerNamespace = 'Ua0leg\\Yii2ActionLog\\controllers';

    /** @var class-string<\yii\db\ActiveRecord>|null */
    public ?string $userModelClass = null;

    /** @var list<string> Namespaces to resolve logged table names to AR classes */
    public array $modelNamespaces = ['app\\models\\'];

    /** @var list<int> User IDs excluded from list/stat queries */
    public array $excludedUserIds = [];

    public int $pageSize = 100;

    public function init(): void
    {
        parent::init();

        $this->setViewPath(__DIR__ . '/views');

        ActionLog::configure([
            'userModelClass'    => $this->userModelClass,
            'modelNamespaces'   => $this->modelNamespaces,
            'excludedUserIds'   => $this->excludedUserIds,
        ]);
    }
}

# yii2-action-log

Yii2 extension that logs ActiveRecord **create**, **update**, and **delete** actions, stores before/after snapshots, and provides an admin UI with statistics and one-click revert.

## Requirements

- PHP 8.2+
- Yii2 ~2.0.45
- MySQL/MariaDB (uses `enum` column type in migration)

## Installation

### Step 1 — Install via Composer

From your Yii2 application root:

```bash
composer require ua0leg/yii2-action-log
```

If the package is not on Packagist yet, add a VCS repository to your app's `composer.json` first:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/ua0leg/yii2-action-log.git"
        }
    ],
    "require": {
        "ua0leg/yii2-action-log": "^1.0"
    }
}
```

Then run `composer update ua0leg/yii2-action-log`.

For local development (same machine, symlinked):

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../yii2-action-log",
            "options": { "symlink": true }
        }
    ],
    "require": {
        "ua0leg/yii2-action-log": "@dev"
    }
}
```

### Step 2 — Register the module

Add the module to `config/web.php`:

```php
'modules' => [
    'action-log' => [
        'class' => \Ua0leg\Yii2ActionLog\Module::class,
        'userModelClass' => \app\models\User::class,
        'modelNamespaces' => ['app\\models\\'],
    ],
],
```

| Option | Description | Default |
|--------|-------------|---------|
| `userModelClass` | Your user AR class (used for labels and relations) | `null` |
| `modelNamespaces` | Prefixes to resolve logged table names to AR classes | `['app\\models\\']` |
| `excludedUserIds` | User IDs hidden from list/stat queries | `[]` |
| `pageSize` | Grid page size on the index page | `100` |

### Step 3 — Run migrations

Run:

```bash
php yii migrate --migrationPath=@vendor/ua0leg/yii2-action-log/src/migrations
```

This creates the `action_log` table.

### Step 4 — Restrict access to the admin UI

The module does not enforce RBAC by itself. Add access rules in your app config, for example:

```php
'modules' => [
    'action-log' => [
        'class' => \Ua0leg\Yii2ActionLog\Module::class,
        'userModelClass' => \app\models\User::class,
        'as access' => [
            'class' => \yii\filters\AccessControl::class,
            'rules' => [
                [
                    'allow' => true,
                    'roles' => ['admin'],
                ],
            ],
        ],
    ],
],
```

Adjust `roles` to match your app's authorization.

### Step 5 — Open the admin UI

After configuration, visit:

- **Log list:** `/action-log`
- **Statistics:** `/action-log/default/stat`

Route prefix follows your module id (`action-log` in the examples above).

---

## Logging model changes

### Option A — Extend `LoggableActiveRecord` (recommended)

Your table must include: `created_at`, `updated_at`, `created_by`, `updated_by`.

```php
<?php

namespace app\models;

use Ua0leg\Yii2ActionLog\LoggableActiveRecord;

class Post extends LoggableActiveRecord
{
    public static function tableName(): string
    {
        return '{{%post}}';
    }
}
```

`LoggableActiveRecord` adds timestamp/blameable behaviors and writes log entries on save and delete automatically.

### Option B — Use the trait on an existing model

If you already have behaviors and lifecycle hooks, use `LoggableActiveRecordTrait`:

```php
<?php

namespace app\models;

use Ua0leg\Yii2ActionLog\traits\LoggableActiveRecordTrait;
use yii\db\ActiveRecord;

class Post extends ActiveRecord
{
    use LoggableActiveRecordTrait;

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
```

### Manual logging

Log custom actions (view, export, print, etc.) anywhere in your app:

```php
use Ua0leg\Yii2ActionLog\models\ActionLog;

ActionLog::add(
    '{{%post}}',           // table name
    $post->id,              // primary key
    ActionLog::ACTION_VIEW, // create | update | delete | view | export | print
    null,                   // before snapshot (array or null)
    $post->attributes       // after snapshot (array or null)
);
```

Snapshots are stored as JSON. Sensitive fields (`password`, `password_hash`, `auth_key`) are masked in the diff UI.

---

## Revert changes

From the log detail page (`/action-log/default/view?id=…`), you can undo or redo individual attribute changes from **update** entries. Revert:

- Works only for `update` actions
- Skips blocked attributes (`id`, timestamps, password fields)
- Writes a new log entry for the revert itself

Programmatic revert:

```php
use Ua0leg\Yii2ActionLog\components\ActionLogRevert;
use Ua0leg\Yii2ActionLog\models\ActionLog;

$log = ActionLog::findOne($id);
ActionLogRevert::apply($log, 'undo');              // all changed attributes
ActionLogRevert::apply($log, 'undo', 'title');     // single attribute
ActionLogRevert::apply($log, 'redo', 'title');
```

---

## Resolving logged models in the UI

The admin UI links log rows to AR records by converting `table_name` to a class name (e.g. `post` → `Post`) and looking it up under `modelNamespaces`.

If your models live elsewhere, extend `modelNamespaces`:

```php
'modelNamespaces' => [
    'app\\models\\',
    'app\\modules\\shop\\models\\',
],
```

Models in registered Yii modules are also discovered automatically (`{ModuleNamespace}models\{ModelName}`).

---

## Publishing this package (maintainers)

### 1. Initialize Git and push to GitHub

```bash
git init
git add .
git commit -m "Initial release"
git branch -M main
git remote add origin https://github.com/ua0leg/yii2-action-log.git
git push -u origin main
```

Use a public repository; Composer reads `composer.json` from the default branch.

### 2. Tag releases

Follow [Semantic Versioning](https://semver.org/). Tag each release:

```bash
git tag v1.0.0
git push origin v1.0.0
```

Consumers can then require `^1.0`.

### 3. Submit to Packagist

1. Sign in at [packagist.org](https://packagist.org) with GitHub.
2. Submit `https://github.com/ua0leg/yii2-action-log`.
3. Enable the GitHub webhook (Packagist prompts this) so new tags update the package automatically.

### 4. GitHub repository checklist

- [ ] `README.md` with install and usage steps (this file)
- [ ] `LICENSE` file (MIT)
- [ ] Tagged releases (`v1.0.0`, …)
- [ ] Issues enabled for bug reports
- [ ] `.gitignore` excludes `vendor/` and `composer.lock`

---

## Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/my-change`
3. Commit with a clear message
4. Open a pull request against `main`

Please include steps to reproduce for bug reports.

## License

MIT. See [LICENSE](LICENSE) if present in the repository.

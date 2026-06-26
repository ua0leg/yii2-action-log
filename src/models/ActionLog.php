<?php

namespace Ua0leg\Yii2ActionLog\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\BaseActiveRecord;
use yii\db\Expression;
use yii\db\Query;
use yii\helpers\Json;

/**
 * @property int $id
 * @property string $table_name
 * @property int $id_model
 * @property int $id_user
 * @property int $ipv4
 * @property string $action
 * @property string|null $before
 * @property string|null $after
 * @property string $created_at
 */
class ActionLog extends ActiveRecord
{
    public const ACTION_CREATE = 'create';
    public const ACTION_VIEW = 'view';
    public const ACTION_UPDATE = 'update';
    public const ACTION_DELETE = 'delete';
    public const ACTION_EXPORT = 'export';
    public const ACTION_PRINT = 'print';

    /** @var class-string<ActiveRecord>|null */
    public static ?string $userModelClass = null;

    /** @var list<string> */
    public static array $modelNamespaces = ['app\\models\\'];

    /** @var list<int> */
    public static array $excludedUserIds = [];

    public static function configure(array $config): void
    {
        foreach ($config as $key => $value) {
            if (property_exists(static::class, $key)) {
                static::$$key = $value;
            }
        }
    }

    public function behaviors(): array
    {
        return [
            'timestamp' => [
                'class'      => TimestampBehavior::class,
                'attributes' => [
                    BaseActiveRecord::EVENT_BEFORE_INSERT => ['created_at'],
                ],
                'value'      => static function () {
                    return gmdate('Y-m-d H:i:s');
                },
            ],
        ];
    }

    public static function tableName(): string
    {
        return '{{%action_log}}';
    }

    public function rules(): array
    {
        return [
            [['id_model', 'id_user', 'ipv4'], 'integer'],
            [['action'], 'string'],
            [['created_at'], 'safe'],
            [['table_name'], 'string', 'max' => 64],
            [['before', 'after'], 'string'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'         => 'ID',
            'table_name' => 'Table',
            'id_model'   => 'Model',
            'id_user'    => 'User',
            'ipv4'       => 'IP',
            'action'     => 'Action',
            'before'     => 'Before',
            'after'      => 'After',
            'created_at' => 'Created',
        ];
    }

    /**
     * @param array<string, mixed>|string|null $before
     * @param array<string, mixed>|string|null $after
     */
    public static function add($table_name, $id_pk, $action, $before = null, $after = null): bool
    {
        $action = array_key_exists($action, self::actionsFilter()) ? $action : self::ACTION_VIEW;
        $log = new self();
        $log->table_name = $table_name;
        $log->id_model = $id_pk;
        $log->id_user = 0;
        if (Yii::$app->has('user')) {
            $log->id_user = (int) Yii::$app->user->getId();
        }
        if (Yii::$app instanceof \yii\web\Application) {
            $log->ipv4 = ip2long(Yii::$app->getRequest()->getUserIP());
        }

        $beforeJson = self::encodeSnapshot($before);
        $afterJson = self::encodeSnapshot($after);
        if ($action === self::ACTION_UPDATE && $beforeJson !== null && $afterJson !== null) {
            $beforeArr = self::decodeSnapshot($beforeJson);
            $afterArr = self::decodeSnapshot($afterJson);
            $newAfter = [];
            foreach ($beforeArr as $key => $value) {
                $newAfter[$key] = $afterArr[$key] ?? null;
            }
            $afterJson = self::encodeSnapshot($newAfter);
        }

        $log->action = $action;
        $log->before = $beforeJson;
        $log->after = $afterJson;

        return $log->save(false);
    }

    public function getNum(): string
    {
        return sprintf('%03d', $this->id);
    }

    /**
     * @param mixed $data
     */
    public static function encodeSnapshot($data): ?string
    {
        if ($data === null || $data === '') {
            return null;
        }
        if (is_string($data)) {
            $trimmed = trim($data);
            if ($trimmed === '') {
                return null;
            }
            if ($trimmed[0] === '{' || $trimmed[0] === '[') {
                return $data;
            }
            $decoded = @unserialize($data, ['allowed_classes' => false]);
            if (!is_array($decoded)) {
                return null;
            }
            $data = $decoded;
        }
        if (!is_array($data) || $data === []) {
            return $data === [] ? Json::encode([], JSON_UNESCAPED_UNICODE) : null;
        }

        return Json::encode($data, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return array<string, mixed>
     */
    public static function decodeSnapshot(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return [];
        }
        $trimmed = ltrim($raw);
        if ($trimmed !== '' && ($trimmed[0] === '{' || $trimmed[0] === '[')) {
            $data = Json::decode($raw, true);

            return is_array($data) ? $data : [];
        }
        $data = @unserialize($raw, ['allowed_classes' => false]);

        return is_array($data) ? $data : [];
    }

    public static function migrateSerializedSnapshot(?string $raw): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }
        $trimmed = ltrim($raw);
        if ($trimmed !== '' && ($trimmed[0] === '{' || $trimmed[0] === '[')) {
            return $raw;
        }

        return self::encodeSnapshot($raw);
    }

    /**
     * @return ActiveQuery|false
     */
    public function getActionModel()
    {
        $modelObject = null;
        $tableName = self::normalizeTableName($this->table_name);
        $modelName = self::dashesToCamelCase($tableName);

        foreach (self::modelNamespaces as $modelNamespace) {
            $class = $modelNamespace . $modelName;
            if (class_exists($class, true)) {
                $modelObject = Yii::createObject(['class' => $class]);
                break;
            }
        }

        foreach (Yii::$app->modules as $module) {
            if (is_array($module) && isset($module['class'])) {
                $modulePath = str_replace('Module', '', $module['class']);
                $class = $modulePath . 'models\\' . $modelName;
                if (class_exists($class, true)) {
                    $modelObject = Yii::createObject(['class' => $class]);
                    break;
                }
            }
        }

        return $modelObject instanceof ActiveRecord
            ? $this->hasOne($modelObject::class, ['id' => 'id_model'])
            : false;
    }

    public static function normalizeTableName(?string $tableName): string
    {
        if ($tableName === null || $tableName === '') {
            return '';
        }
        if (strncmp($tableName, '{{%', 3) === 0 && substr($tableName, -2) === '}}') {
            return substr($tableName, 3, -2);
        }

        return $tableName;
    }

    public function getActionModelLabel(): string
    {
        $fallback = self::normalizeTableName($this->table_name) . ' #' . $this->id_model;

        $target = $this->actionModel;
        if (!$target instanceof ActiveRecord) {
            return $fallback;
        }

        if (method_exists($target, 'getName')) {
            $name = $target->getName();
            if ($name !== null && $name !== '') {
                return (string) $name;
            }
        }

        if ($target->hasAttribute('name')) {
            $name = $target->getAttribute('name');
            if ($name !== null && $name !== '') {
                return (string) $name;
            }
        }

        if ($target->hasAttribute('title')) {
            $title = $target->getAttribute('title');
            if ($title !== null && $title !== '') {
                return (string) $title;
            }
        }

        return $fallback;
    }

    /**
     * @return ActiveQuery|false
     */
    public function getUser()
    {
        if (static::$userModelClass === null) {
            return false;
        }

        return $this->hasOne(static::$userModelClass, ['id' => 'id_user']);
    }

    public function getUserLabel(): string
    {
        if (!$this->id_user) {
            return '';
        }

        $user = $this->user;
        if ($user instanceof ActiveRecord) {
            foreach (['username', 'name', 'email'] as $attr) {
                if ($user->hasAttribute($attr)) {
                    $value = $user->getAttribute($attr);
                    if ($value !== null && $value !== '') {
                        return (string) $value;
                    }
                }
            }
        }

        return '#' . $this->id_user;
    }

    public function getIp(): string
    {
        return long2ip($this->ipv4);
    }

    /**
     * @return array<string, string>
     */
    public static function actionsFilter(): array
    {
        return [
            self::ACTION_CREATE => 'Create',
            self::ACTION_VIEW   => 'View',
            self::ACTION_UPDATE => 'Update',
            self::ACTION_DELETE => 'Delete',
            self::ACTION_EXPORT => 'Export',
            self::ACTION_PRINT  => 'Print',
        ];
    }

    public function getActionName(): string
    {
        $array = self::actionsFilter();

        return array_key_exists($this->action, $array) ? $array[$this->action] : '-';
    }

    /**
     * @return list<array{attribute: string, old: mixed, new: mixed}>
     */
    public static function diffRows(?string $before, ?string $after): array
    {
        $beforeArr = self::decodeSnapshot($before);
        $afterArr = self::decodeSnapshot($after);
        $sensitive = ['password', 'password_hash', 'auth_key'];
        $keys = array_unique(array_merge(array_keys($beforeArr), array_keys($afterArr)));
        sort($keys, SORT_STRING);
        $out = [];
        foreach ($keys as $key) {
            $old = array_key_exists($key, $beforeArr) ? $beforeArr[$key] : null;
            $new = array_key_exists($key, $afterArr) ? $afterArr[$key] : null;
            if (self::logValuesStrictlyEqual($old, $new)) {
                continue;
            }
            if (in_array($key, $sensitive, true)) {
                if ($old !== null && $old !== '') {
                    $old = '***';
                }
                if ($new !== null && $new !== '') {
                    $new = '***';
                }
            }
            $out[] = ['attribute' => (string) $key, 'old' => $old, 'new' => $new];
        }

        return $out;
    }

    /**
     * @param mixed $a
     * @param mixed $b
     */
    public static function logValuesStrictlyEqual($a, $b): bool
    {
        return Json::encode($a, JSON_UNESCAPED_UNICODE) === Json::encode($b, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param mixed $a
     * @param mixed $b
     */
    public static function logValuesEffectivelyEqual($a, $b): bool
    {
        $na = self::normalizeNullishForCompare($a);
        $nb = self::normalizeNullishForCompare($b);
        if (Json::encode($na) === Json::encode($nb)) {
            return true;
        }
        if (is_array($na) || is_array($nb) || is_object($na) || is_object($nb)) {
            return false;
        }
        if (is_bool($na) || is_bool($nb)) {
            return $na === $nb;
        }

        return (string) $na === (string) $nb;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private static function normalizeNullishForCompare($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $value;
    }

    public static function formatLogValue($value): string
    {
        if ($value === null) {
            return '—';
        }
        if ($value === '') {
            return '(empty)';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }
        if (is_string($value)) {
            return $value;
        }

        return Json::encode($value, JSON_UNESCAPED_UNICODE);
    }

    public function getCssClass(): string
    {
        $arr = [
            self::ACTION_CREATE => 'success',
            self::ACTION_VIEW   => 'info',
            self::ACTION_UPDATE => 'warning',
            self::ACTION_DELETE => 'danger',
            self::ACTION_EXPORT => 'primary',
            self::ACTION_PRINT  => 'secondary',
        ];

        return array_key_exists($this->action, $arr) ? $arr[$this->action] : 'primary';
    }

    public static function dashesToCamelCase($string, $capitalizeFirstCharacter = true): string
    {
        $str = str_replace('_', '', ucwords($string, '_'));

        if ($capitalizeFirstCharacter) {
            $str = ucfirst($str);
        }

        return $str;
    }

    /**
     * @return array{
     *     totals: array{all: int, create: int, update: int, delete: int, other: int},
     *     topUsers: list<array{id_user: int, name: string, count: int}>,
     *     topTables: list<array{table_name: string, count: int}>,
     *     byDay: list<array{date: string, count: int}>
     * }
     */
    public static function getStatistics(int $days = 30): array
    {
        $db = static::getDb();
        $where = ['and'];
        foreach (static::$excludedUserIds as $userId) {
            $where[] = ['!=', 'id_user', $userId];
        }
        if ($days > 0) {
            $where[] = ['>=', 'created_at', gmdate('Y-m-d H:i:s', time() - $days * 86400)];
        }
        if ($where === ['and']) {
            $where = [];
        }

        $actionCounts = (new Query())
            ->select(['action', 'cnt' => new Expression('COUNT(*)')])
            ->from(static::tableName())
            ->where($where)
            ->groupBy('action')
            ->all($db);

        $totals = ['all' => 0, 'create' => 0, 'update' => 0, 'delete' => 0, 'other' => 0];
        foreach ($actionCounts as $row) {
            $cnt = (int) $row['cnt'];
            $totals['all'] += $cnt;
            if (isset($totals[$row['action']])) {
                $totals[$row['action']] = $cnt;
            } else {
                $totals['other'] += $cnt;
            }
        }

        $topUserRows = (new Query())
            ->select(['id_user', 'cnt' => new Expression('COUNT(*)')])
            ->from(static::tableName())
            ->where($where)
            ->andWhere(['>', 'id_user', 0])
            ->groupBy('id_user')
            ->orderBy(['cnt' => SORT_DESC])
            ->limit(5)
            ->all($db);

        $userIds = array_column($topUserRows, 'id_user');
        $users = [];
        if ($userIds !== [] && static::$userModelClass !== null) {
            $users = static::$userModelClass::find()->where(['id' => $userIds])->indexBy('id')->all();
        }

        $topUsers = [];
        foreach ($topUserRows as $row) {
            $user = $users[$row['id_user']] ?? null;
            $name = '#' . $row['id_user'];
            if ($user instanceof ActiveRecord) {
                foreach (['username', 'name', 'email'] as $attr) {
                    if ($user->hasAttribute($attr)) {
                        $value = $user->getAttribute($attr);
                        if ($value !== null && $value !== '') {
                            $name = (string) $value;
                            break;
                        }
                    }
                }
            }
            $topUsers[] = [
                'id_user' => (int) $row['id_user'],
                'name'    => $name,
                'count'   => (int) $row['cnt'],
            ];
        }

        $tableRows = (new Query())
            ->select(['table_name', 'cnt' => new Expression('COUNT(*)')])
            ->from(static::tableName())
            ->where($where)
            ->groupBy('table_name')
            ->orderBy(['cnt' => SORT_DESC])
            ->limit(5)
            ->all($db);

        $topTables = [];
        foreach ($tableRows as $row) {
            $topTables[] = [
                'table_name' => $row['table_name'],
                'count'      => (int) $row['cnt'],
            ];
        }

        $dayLimit = $days > 0 ? min($days, 30) : 30;
        $byDayWhere = ['and'];
        foreach (static::$excludedUserIds as $userId) {
            $byDayWhere[] = ['!=', 'id_user', $userId];
        }
        $byDayWhere[] = ['>=', 'created_at', gmdate('Y-m-d 00:00:00', time() - ($dayLimit - 1) * 86400)];

        $dayRows = (new Query())
            ->select(['date' => new Expression('DATE(created_at)'), 'cnt' => new Expression('COUNT(*)')])
            ->from(static::tableName())
            ->where($byDayWhere)
            ->groupBy(new Expression('DATE(created_at)'))
            ->orderBy(['date' => SORT_ASC])
            ->all($db);

        $byDay = [];
        foreach ($dayRows as $row) {
            $byDay[] = [
                'date'  => $row['date'],
                'count' => (int) $row['cnt'],
            ];
        }

        return [
            'totals'    => $totals,
            'topUsers'  => $topUsers,
            'topTables' => $topTables,
            'byDay'     => $byDay,
        ];
    }
}

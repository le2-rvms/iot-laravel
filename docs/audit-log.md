# Audit Log

## migration

`audits` 表字段：

- `auditable_type`
- `auditable_id`
- `actor_id`
- `event`
- `old_values`
- `new_values`
- `meta`
- `created_at`

当前仓库实现见：

- `database/migrations/2026_03_24_000000_create_audits_table.php`

## model

`Audit` 模型职责：

- `morphTo auditable()`
- `belongsTo actor()`，指向当前后台用户 `App\Models\Auth\AdminUser`
- `old_values / new_values / meta` JSON cast

当前仓库实现见：

- `app/Models/Audit.php`

## logger

`AuditLogger` 提供统一入口：

```php
AuditLogger::created($model);
AuditLogger::updated($model);
AuditLogger::deleted($model, $snapshot);
AuditLogger::restored($model);
AuditLogger::forceDeleted($model, $snapshot);

AuditLogger::custom(
    $post,
    'approved',
    old: ['status' => 'pending'],
    new: ['status' => 'approved'],
    meta: ['reason' => 'manual_review_passed'],
);
```

当前仓库实现见：

- `app/Support/Audit/AuditLogger.php`

## trait

任意 Eloquent 模型挂上 `Auditable` 即可自动记录：

- `created`
- `updated`
- `deleted`
- `restored`
- `force_deleted`

扩展点：

```php
public function auditExcept(): array
```

当前仓库实现见：

- `app/Models/Concerns/Auditable.php`

## 示例

### 当前仓库接入

```php
use App\Models\Concerns\Auditable;

class AdminUser extends Authenticatable
{
    use Auditable;
}
```

```php
use App\Models\Concerns\Auditable;

class MqttAccount extends Model
{
    use Auditable;

    public function auditExcept(): array
    {
        return array_values(array_unique([
            $this->getKeyName(),
            'password',
            'remember_token',
            $this->getCreatedAtColumn(),
            $this->getUpdatedAtColumn(),
            'password_hash',
            'salt',
            'certificate',
        ]));
    }
}
```

### 标准 Laravel `Post` 示例

```php
namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use SoftDeletes, Auditable;

    protected $guarded = [];
}
```

### 标准 Laravel `User` 示例

```php
namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Auditable;

    protected $guarded = [];

    public function auditExcept(): array
    {
        return array_values(array_unique([
            $this->getKeyName(),
            'password',
            'remember_token',
            $this->getCreatedAtColumn(),
            $this->getUpdatedAtColumn(),
        ]));
    }
}
```

### 查询示例

```php
$post->audits()->latest()->get();

Audit::query()
    ->where('actor_id', $userId)
    ->latest()
    ->get();

Audit::query()
    ->where('event', 'refunded')
    ->latest()
    ->get();
```

## 限制说明

以下操作不会触发 Eloquent 模型事件，因此不会自动产生日志：

- 批量 `query()->update()`
- 批量 `query()->delete()`
- 批量 `query()->restore()`
- `saveQuietly()`
- `updateQuietly()`
- `deleteQuietly()`
- `restoreQuietly()`
- `forceDeleteQuietly()`
- `Model::withoutEvents(...)`

这类场景如需审计，应显式调用：

```php
AuditLogger::custom($model, 'published', old: [...], new: [...], meta: [...]);
```

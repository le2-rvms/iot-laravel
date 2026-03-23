<?php

namespace App\Models\Iot;

use App\Models\Concerns\ModelSupport;
use App\Values\Iot\Enabled;
use App\Values\Iot\IsSuperuser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * @property int $act_id 账号ID
 * @property string|null $clientid 客户端标识
 * @property string $user_name 账号名
 * @property string|null $password 密码
 * @property string $password_hash 密码哈希
 * @property string|null $certificate 证书内容
 * @property string|null $salt 密码盐
 * @property IsSuperuser $is_superuser 是否超级用户
 * @property string|null $product_key 产品标识
 * @property string|null $device_name 设备名称
 * @property Enabled $enabled 启用状态
 * @property Carbon|null $act_created_at 创建时间
 * @property Carbon|null $act_updated_at 更新时间
 * @property string|null $act_updated_by 最近更新人
 */
class MqttAccount extends Model
{
    use HasFactory;
    use ModelSupport;

    // 列表、表单和 Inertia 响应都不应暴露真实哈希与盐值。
    protected $hidden = [
        'password_hash',
        'salt',
    ];

    public const CREATED_AT = 'act_created_at';

    public const UPDATED_AT = 'act_updated_at';

    public const UPDATED_BY = 'act_updated_by';

    protected $table = 'mqtt_accounts';

    protected $primaryKey = 'act_id';

    protected $guarded = ['act_id'];

    /**
     * @return array<int, string>
     */
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

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_superuser' => IsSuperuser::class,
            'enabled' => Enabled::class,
            'act_created_at' => 'datetime',
            'act_updated_at' => 'datetime',
        ];
    }

    public function checkPassword(string $password): bool
    {
        if (! is_string($this->salt) || $this->salt === '') {
            return false;
        }

        // 鉴权阶段继续复用旧系统哈希规则，避免迁移后现有 MQTT 账号全部失效。
        return hash_equals((string) $this->password_hash, static::makePasswordHash($password, $this->salt));
    }

    public static function makePasswordHash(string $password, string $salt): string
    {
        return hash('sha256', $password.$salt);
    }

    /**
     * 继续沿用旧系统的 salt + sha256 规则，保证 EMQX 存量账号可直接迁移。
     *
     * @return array{salt: string, password_hash: string}
     */
    public static function buildPasswordFields(string $password): array
    {
        $salt = Str::random(10);

        return [
            'salt' => $salt,
            'password_hash' => static::makePasswordHash($password, $salt),
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function createAccount(array $attributes, string $password): self
    {
        return DB::transaction(function () use ($attributes, $password): self {
            $mqttAccount = (new self)->fill($attributes + self::buildPasswordFields($password));

            $mqttAccount->save();

            return $mqttAccount;
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateAccount(array $attributes, ?string $password = null): self
    {
        return DB::transaction(function () use ($attributes, $password): self {
            if (filled($password)) {
                $attributes += self::buildPasswordFields($password);
            }

            $this->update($attributes);

            return $this->fresh();
        });
    }

    public function deleteAccount(): void
    {
        DB::transaction(function (): void {
            $this->delete();
        });
    }
}

<?php

namespace App\Models;

use App\Services\DefaultConfig;

class GConfig extends Model
{
    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'default';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'gconfig';

    /**
     * 恢复默认配置
     *
     * @param User $user
     *
     * @return void
     */
    public function recover(User $user): void
    {
        $this->oldvalue       = $this->value;
        $this->value          = DefaultConfig::default_value($this->key)['value'];
        $this->operator_id    = $user->id;
        $this->operator_name  = ('[恢复默认] - ' . $user->user_name);
        $this->operator_email = $user->email;
        $this->last_update    = time();
        $this->save();
    }

    /**
     * 获取配置值
     *
     * @return mixed
     */
    public function getValue(): mixed
    {
        return match ($this->type) {
            'bool' => (bool)$this->value,
            'array' => json_decode($this->value, true),
            'string' => (string)$this->value,
            default => (string)$this->value,
        };
    }

    /**
     * 设定配置值
     *
     * @param mixed $value
     * @param User|null $user
     *
     * @return bool
     */
    public function setValue(mixed $value, User $user = null): bool
    {
        $this->oldvalue = $this->value;
        $this->value    = $this->typeConversion($value);
        if ($user === null) {
            $this->operator_id    = 0;
            $this->operator_name  = '系统修改';
            $this->operator_email = 'admin@admin.com';
        } else {
            $this->operator_id    = $user->id;
            $this->operator_name  = $user->user_name;
            $this->operator_email = $user->email;
        }
        $this->last_update = time();
        if (!$this->save()) {
            return false;
        }
        return true;
    }

    /**
     * 配置值得类型转换
     *
     * @param mixed $value
     *
     * @return false|string
     */
    public function typeConversion(mixed $value): bool|string
    {
        return match ($this->type) {
            'bool' => (string)$value,
            'array' => json_encode($value, 320),
            'string' => (string)$value,
            default => (string)$value,
        };
    }
}

<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait TracksUpdatedBy
{
    protected bool $preserveUpdatedBy = false;

    protected static function bootTracksUpdatedBy(): void
    {
        static::saving(function (Model $model): void {
            $column = $model->updatedByColumn();

            if ($column === null) {
                return;
            }

            // 调用方明确保留本次更新人，或已显式修改该字段时，都不再由 trait 自动回填。
            if ($model->preserveUpdatedBy || $model->isDirty($column)) {
                return;
            }

            if ($model->exists && ! $model->isDirty()) {
                return;
            }

            $user = Auth::user();
            $value = $user ? (data_get($user, 'email') ?? $user->getAuthIdentifier()) : null;

            if ($value === null || $value === '') {
                return;
            }

            $model->setAttribute($column, $value);
        });
    }

    public function savePreservingUpdatedBy(array $options = []): bool
    {
        // 仅对当前这一次 save 保留调用方显式设置的更新人，try/finally 用于避免状态泄漏到后续保存。
        $this->preserveUpdatedBy = true;

        try {
            return $this->save($options);
        } finally {
            $this->preserveUpdatedBy = false;
        }
    }

    protected function updatedByColumn(): ?string
    {
        $constant = static::class.'::UPDATED_BY';

        if (! defined($constant)) {
            return null;
        }

        $column = constant($constant);

        return is_string($column) && $column !== '' ? $column : null;
    }
}

<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasDelStatus
{
    protected static function bootHasDelStatus(): void
    {
        static::creating(function ($model) {
            if (empty($model->del_status)) {
                $model->del_status = 'live';
            }
        });

        static::addGlobalScope('del_status_live', function (Builder $builder) {
            $builder->where($builder->getModel()->getTable() . '.del_status', 'live');
        });
    }

    public function scopeWithDeleted(Builder $query): Builder
    {
        return $query->withoutGlobalScope('del_status_live');
    }

    public function scopeOnlyDeleted(Builder $query): Builder
    {
        return $query->withoutGlobalScope('del_status_live')
            ->where($this->getTable() . '.del_status', 'deleted');
    }

    public function markDeleted(): bool
    {
        return $this->update(['del_status' => 'deleted']);
    }
}

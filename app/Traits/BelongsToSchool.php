<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToSchool
{
    protected static function bootBelongsToSchool(): void
    {
        // Automatically scope all queries to the current tenant
        static::addGlobalScope('school', function (Builder $query) {
            if (app()->bound('currentSchool')) {
                $query->where(
                    (new static())->getTable() . '.school_id',
                    app('currentSchool')->id
                );
            }
        });

        // Automatically assign school_id on create
        static::creating(function ($model) {
            if (app()->bound('currentSchool') && empty($model->school_id)) {
                $model->school_id = app('currentSchool')->id;
            }
        });
    }
}

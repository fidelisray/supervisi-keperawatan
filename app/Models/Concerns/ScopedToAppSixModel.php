<?php

namespace App\Models\Concerns;

use App\Models\AppRole;
use BadMethodCallException;
use Illuminate\Database\Eloquent\Model;

trait ScopedToAppSixModel
{
    public static function bootScopedToAppSixModel(): void
    {
        static::creating(function (Model $model): void {
            if (isset($model->attributes['app_id']) && (int) $model->attributes['app_id'] !== AppRole::APP_ID) {
                throw new BadMethodCallException(sprintf(
                    'Model [%s] only permits creating records for app_id = %d.',
                    static::class,
                    AppRole::APP_ID
                ));
            }

            $model->setAttribute('app_id', AppRole::APP_ID);
        });

        static::saving(function (Model $model): void {
            $originalAppId = $model->getOriginal('app_id');

            if ($originalAppId !== null && (int) $originalAppId !== AppRole::APP_ID) {
                throw new BadMethodCallException(sprintf(
                    'Model [%s] cannot modify records belonging to app_id [%s]. Only app_id = %d is permitted.',
                    static::class,
                    (string) $originalAppId,
                    AppRole::APP_ID
                ));
            }

            if (isset($model->attributes['app_id']) && (int) $model->attributes['app_id'] !== AppRole::APP_ID) {
                throw new BadMethodCallException(sprintf(
                    'Model [%s] only permits saving records for app_id = %d.',
                    static::class,
                    AppRole::APP_ID
                ));
            }

            $model->setAttribute('app_id', AppRole::APP_ID);
        });

        static::updating(function (Model $model): void {
            $originalAppId = $model->getOriginal('app_id');

            if ($originalAppId !== null && (int) $originalAppId !== AppRole::APP_ID) {
                throw new BadMethodCallException(sprintf(
                    'Model [%s] cannot update records belonging to app_id [%s].',
                    static::class,
                    (string) $originalAppId
                ));
            }
        });

        static::deleting(function (Model $model): void {
            $appId = $model->getOriginal('app_id') ?? $model->getAttribute('app_id');

            if ($appId !== null && (int) $appId !== AppRole::APP_ID) {
                throw new BadMethodCallException(sprintf(
                    'Model [%s] cannot delete records belonging to app_id [%s]. Only app_id = %d is permitted.',
                    static::class,
                    (string) $appId,
                    AppRole::APP_ID
                ));
            }
        });
    }
}

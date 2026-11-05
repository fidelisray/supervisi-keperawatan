<?php

namespace App\Models\Concerns;

use BadMethodCallException;

trait ReadOnlyModel
{
    /**
     * Boot the trait to hook into Eloquent model saving and deleting events.
     */
    public static function bootReadOnlyModel(): void
    {
        static::saving(function ($model): void {
            throw new BadMethodCallException(sprintf('Model [%s] is read-only and cannot be modified.', static::class));
        });

        static::deleting(function ($model): void {
            throw new BadMethodCallException(sprintf('Model [%s] is read-only and cannot be deleted.', static::class));
        });
    }

    /**
     * Prevent direct save operations.
     *
     * @param  array<string, mixed>  $options
     */
    public function save(array $options = []): bool
    {
        throw new BadMethodCallException(sprintf('Model [%s] is read-only and cannot be saved.', static::class));
    }

    /**
     * Prevent direct delete operations.
     */
    public function delete(): ?bool
    {
        throw new BadMethodCallException(sprintf('Model [%s] is read-only and cannot be deleted.', static::class));
    }

    /**
     * Prevent direct force delete operations.
     */
    public function forceDelete(): ?bool
    {
        throw new BadMethodCallException(sprintf('Model [%s] is read-only and cannot be force deleted.', static::class));
    }
}

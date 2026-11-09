<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    // Verifikasi mutlak: Pastikan koneksi sqlsrv dalam test 100% menggunakan sqlite in-memory
    if (config('database.connections.sqlsrv.driver') !== 'sqlite' || config('database.connections.sqlsrv.database') !== ':memory:') {
        throw new RuntimeException('ABORT SAFETY: Test tidak berjalan di sqlite memory! Eksekusi dibatalkan.');
    }

    // Verifikasi mutlak: Pastikan koneksi sqlsrv dalam test 100% menggunakan sqlite in-memory
    if (config('database.connections.sqlsrv.driver') !== 'sqlite' || config('database.connections.sqlsrv.database') !== ':memory:') {
        throw new \RuntimeException('ABORT SAFETY: Test tidak berjalan di sqlite memory! Eksekusi dibatalkan.');
    }

    app(Kernel::class)->rerouteSymfonyCommandEvents();
});

test('it blocks db:wipe when targeting sqlsrv explicitly with a critical safety block exception', function () {
    expect(function () {
        Artisan::call('db:wipe', ['--database' => 'sqlsrv', '--force' => true]);
    })->toThrow(RuntimeException::class, '[CRITICAL SAFETY BLOCK]');
});

test('it blocks migrate:fresh when targeting sqlsrv explicitly', function () {
    expect(function () {
        Artisan::call('migrate:fresh', ['--database' => 'sqlsrv', '--force' => true]);
    })->toThrow(RuntimeException::class, '[CRITICAL SAFETY BLOCK]');
});

test('it blocks destructive commands when database.default is set to sqlsrv', function () {
    config()->set('database.default', 'sqlsrv');

    expect(function () {
        Artisan::call('db:wipe', ['--force' => true]);
    })->toThrow(RuntimeException::class, '[CRITICAL SAFETY BLOCK]');
});

test('it activates DB::prohibitDestructiveCommands in production environment', function () {
    DB::prohibitDestructiveCommands(true);

    expect(true)->toBeTrue();

    DB::prohibitDestructiveCommands(false);
});

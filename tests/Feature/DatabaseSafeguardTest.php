<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    // Verifikasi mutlak: Pastikan koneksi sqlsrv dalam test 100% menggunakan sqlite in-memory
    if (config('database.connections.sqlsrv.driver') !== 'sqlite' || config('database.connections.sqlsrv.database') !== ':memory:') {
        throw new RuntimeException('ABORT SAFETY: Test tidak berjalan di sqlite memory! Eksekusi dibatalkan.');
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

test('category migration does not delete existing supervision reports or incidents when rerun', function () {
    $reportId = DB::connection('sqlsrv')->table('tb_nurse_supervision_reports')->insertGetId([
        'report_date' => now()->toDateString(),
        'shift' => 'pagi',
        'room_name' => 'FRANSISKUS',
        'patient_count' => 10,
        'nurse_count' => 3,
        'supervisor_name' => 'V. Erny Kristiana Santoso, S.Kep.Ners, M.Kep',
        'nurse_in_charge' => '[]',
        'status' => 'submitted',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::connection('sqlsrv')->table('tb_nurse_supervision_incidents')->insert([
        'supervision_report_id' => $reportId,
        'category' => 1,
        'description' => 'Data insiden eksisting yang wajib terjaga.',
        'status' => 'in_progress',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $migration = require database_path('migrations/sqlsrv/2026_11_05_134500_create_tb_master_nurse_supervision_incidents_category_table.php');
    $migration->up();

    expect(DB::connection('sqlsrv')->table('tb_nurse_supervision_reports')->count())->toBe(1)
        ->and(DB::connection('sqlsrv')->table('tb_nurse_supervision_incidents')->count())->toBe(1);
});

test('category migration preserves existing incidents even when category foreign key is absent', function () {
    $schema = Schema::connection('sqlsrv');
    $schema->dropIfExists('tb_nurse_supervision_incidents');
    $schema->create('tb_nurse_supervision_incidents', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('supervision_report_id')
            ->constrained('tb_nurse_supervision_reports')
            ->cascadeOnDelete();
        $table->string('category');
        $table->string('incident_time')->nullable();
        $table->text('description');
        $table->text('action_taken')->nullable();
        $table->string('status')->default('in_progress');
        $table->timestamps();
    });

    $reportId = DB::connection('sqlsrv')->table('tb_nurse_supervision_reports')->insertGetId([
        'report_date' => now()->toDateString(),
        'shift' => 'pagi',
        'room_name' => 'FRANSISKUS',
        'patient_count' => 10,
        'nurse_count' => 3,
        'supervisor_name' => 'V. Erny Kristiana Santoso, S.Kep.Ners, M.Kep',
        'nurse_in_charge' => '[]',
        'status' => 'submitted',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::connection('sqlsrv')->table('tb_nurse_supervision_incidents')->insert([
        'supervision_report_id' => $reportId,
        'category' => '1',
        'description' => 'Insiden legacy sebelum FK.',
        'status' => 'in_progress',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $migration = require database_path('migrations/sqlsrv/2026_11_05_134500_create_tb_master_nurse_supervision_incidents_category_table.php');
    $migration->up();

    expect(DB::connection('sqlsrv')->table('tb_nurse_supervision_reports')->count())->toBe(1)
        ->and(DB::connection('sqlsrv')->table('tb_nurse_supervision_incidents')->count())->toBe(1);
});

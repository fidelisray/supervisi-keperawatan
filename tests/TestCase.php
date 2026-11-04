<?php

namespace Tests;

use App\Models\Employee;
use App\Models\Room;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.connections.sqlsrv', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $schema = Schema::connection('sqlsrv');

        if (! $schema->hasTable('master_instalasi_ruang')) {
            $schema->create('master_instalasi_ruang', function (Blueprint $table) {
                $table->string('id_ruang', 10)->primary();
                $table->string('nama_ruangan', 50);
                $table->string('instalasi', 50)->nullable();
            });

            Room::create([
                'id_ruang' => 'A_FRAS',
                'nama_ruangan' => 'FRANSISKUS',
                'instalasi' => 'A',
            ]);
        }

        if (! $schema->hasTable('tb_employees')) {
            $schema->create('tb_employees', function (Blueprint $table) {
                $table->string('nip', 256)->primary();
                $table->string('nama', 256);
                $table->string('gender', 50)->nullable();
                $table->string('id_profesi', 12)->nullable();
                $table->string('id_unit', 12)->nullable();
                $table->string('status_karyawan', 50)->nullable();
                $table->string('pin', 256)->nullable();
                $table->string('foto_profil', 256)->nullable();
            });

            Employee::create([
                'nip' => '5508150824',
                'nama' => 'Purwanto Ali Sastra, S.Kom',
                'status_karyawan' => 'AKTIF',
            ]);
            Employee::create([
                'nip' => '1355010892',
                'nama' => 'Cicilia Rumini, S.Kep.Ners',
                'status_karyawan' => 'AKTIF',
            ]);
        }

        if (! $schema->hasTable('tb_nurse_supervision_reports')) {
            $schema->create('tb_nurse_supervision_reports', function (Blueprint $table) {
                $table->id();
                $table->date('report_date');
                $table->string('shift');
                $table->string('room_name');
                $table->unsignedInteger('patient_count')->default(0);
                $table->unsignedInteger('nurse_count')->default(0);
                $table->string('supervisor_name');
                $table->string('nurse_in_charge');
                $table->text('general_notes')->nullable();
                $table->string('status')->default('submitted');
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('tb_nurse_supervision_incidents')) {
            $schema->create('tb_nurse_supervision_incidents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supervision_report_id')->constrained('tb_nurse_supervision_reports')->cascadeOnDelete();
                $table->string('category');
                $table->string('incident_time')->nullable();
                $table->text('description');
                $table->text('action_taken')->nullable();
                $table->string('status')->default('in_progress');
                $table->timestamps();
            });
        }
    }
}

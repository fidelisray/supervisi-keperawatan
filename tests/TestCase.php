<?php

namespace Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
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

            DB::connection('sqlsrv')->table('master_instalasi_ruang')->insert([
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
                $table->string('id_golongan', 12)->nullable();
                $table->string('id_unit', 12)->nullable();
                $table->string('status_karyawan', 50)->nullable();
                $table->string('pin', 256)->nullable();
                $table->string('foto_profil', 256)->nullable();
            });

            DB::connection('sqlsrv')->table('tb_employees')->insert([
                [
                    'nip' => '5508150824',
                    'nama' => 'Purwanto Ali Sastra, S.Kom',
                    'id_profesi' => '888',
                    'id_golongan' => '4A1',
                    'status_karyawan' => 'AKTIF',
                ],
                [
                    'nip' => '1355010892',
                    'nama' => 'Cicilia Rumini, S.Kep.Ners',
                    'id_profesi' => '888',
                    'id_golongan' => '4A1',
                    'status_karyawan' => 'AKTIF',
                ],
            ]);
        }

        if (! $schema->hasTable('tb_approles')) {
            $schema->create('tb_approles', function (Blueprint $table) {
                $table->id();
                $table->string('nip', 30);
                $table->integer('app_id');
                $table->integer('app_roles');
                $table->string('description', 255)->nullable();
                $table->string('push_token', 255)->nullable();
            });

            DB::connection('sqlsrv')->table('tb_approles')->insert([
                [
                    'nip' => '5508150824',
                    'app_id' => 6,
                    'app_roles' => 0,
                    'description' => 'Purwanto Ali',
                    'push_token' => null,
                ],
                [
                    'nip' => '1672010896',
                    'app_id' => 6,
                    'app_roles' => 1,
                    'description' => 'V. Erny Kristiana Santoso, S.Kep.Ners, M.Kep',
                    'push_token' => null,
                ],
                [
                    'nip' => '2351151100',
                    'app_id' => 6,
                    'app_roles' => 1,
                    'description' => 'Anastasia Naning Sudiar, S.Kep.Ners',
                    'push_token' => null,
                ],
                [
                    'nip' => '9000000001',
                    'app_id' => 1,
                    'app_roles' => 1,
                    'description' => 'Pegawai Aplikasi Lain',
                    'push_token' => null,
                ],
            ]);
        }

        if (! $schema->hasTable('tb_globalaccount')) {
            $schema->create('tb_globalaccount', function (Blueprint $table) {
                $table->id();
                $table->string('nip', 30);
                $table->string('kata_sandi', 255);
                $table->string('device_id', 255)->nullable();
                $table->timestamp('last_update')->nullable();
            });

            DB::connection('sqlsrv')->table('tb_globalaccount')->insert([
                [
                    'nip' => '5508150824',
                    'kata_sandi' => base64_encode('123456'),
                    'device_id' => null,
                ],
                [
                    'nip' => '1672010896',
                    'kata_sandi' => base64_encode('rahasia123'),
                    'device_id' => null,
                ],
                [
                    'nip' => '9000000001',
                    'kata_sandi' => base64_encode('123456'),
                    'device_id' => null,
                ],
            ]);
        }

        if (! $schema->hasTable('ref_approles')) {
            $schema->create('ref_approles', function (Blueprint $table) {
                $table->id();
                $table->integer('role_id');
                $table->string('role_name', 100);
                $table->integer('app_id');
            });

            DB::connection('sqlsrv')->table('ref_approles')->insert([
                [
                    'role_id' => 0,
                    'role_name' => 'ADMINISTRATOR',
                    'app_id' => 6,
                ],
                [
                    'role_id' => 1,
                    'role_name' => 'SUPERVISOR KEPERAWATAN',
                    'app_id' => 6,
                ],
            ]);
        }

        if (! $schema->hasTable('ref_master_nurse_supervision_approles')) {
            $schema->create('ref_master_nurse_supervision_approles', function (Blueprint $table) {
                $table->id();
                $table->integer('role_id');
                $table->string('role_name', 100);
                $table->integer('app_id')->default(6);
            });

            DB::connection('sqlsrv')->table('ref_master_nurse_supervision_approles')->insert([
                [
                    'role_id' => 0,
                    'role_name' => 'ADMINISTRATOR',
                    'app_id' => 6,
                ],
                [
                    'role_id' => 1,
                    'role_name' => 'SUPERVISOR KEPERAWATAN',
                    'app_id' => 6,
                ],
            ]);
        }

        if (! $schema->hasTable('tb_master_nurse_supervision_approles')) {
            $schema->create('tb_master_nurse_supervision_approles', function (Blueprint $table) {
                $table->id();
                $table->string('nip', 30);
                $table->integer('app_id')->default(6);
                $table->integer('app_roles');
                $table->string('description', 255)->nullable();
                $table->string('push_token', 255)->nullable();
            });

            DB::connection('sqlsrv')->table('tb_master_nurse_supervision_approles')->insert([
                [
                    'nip' => '5508150824',
                    'app_id' => 6,
                    'app_roles' => 0,
                    'description' => 'Purwanto Ali',
                    'push_token' => null,
                ],
                [
                    'nip' => '1672010896',
                    'app_id' => 6,
                    'app_roles' => 1,
                    'description' => 'V. Erny Kristiana Santoso, S.Kep.Ners, M.Kep',
                    'push_token' => null,
                ],
                [
                    'nip' => '2351151100',
                    'app_id' => 6,
                    'app_roles' => 1,
                    'description' => 'Anastasia Naning Sudiar, S.Kep.Ners',
                    'push_token' => null,
                ],
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

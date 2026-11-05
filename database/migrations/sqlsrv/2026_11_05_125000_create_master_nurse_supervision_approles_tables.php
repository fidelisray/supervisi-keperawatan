<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The database connection that should be used by the migration.
     *
     * @var string
     */
    protected $connection = 'sqlsrv';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::connection('sqlsrv')->hasTable('ref_master_nurse_supervision_approles')) {
            Schema::connection('sqlsrv')->create('ref_master_nurse_supervision_approles', function (Blueprint $table) {
                $table->id();
                $table->integer('role_id');
                $table->string('role_name', 100);
                $table->integer('app_id')->default(6);

                $table->index(['app_id', 'role_id']);
            });
        }

        if (! Schema::connection('sqlsrv')->hasTable('tb_master_nurse_supervision_approles')) {
            Schema::connection('sqlsrv')->create('tb_master_nurse_supervision_approles', function (Blueprint $table) {
                $table->id();
                $table->string('nip', 30);
                $table->integer('app_id')->default(6);
                $table->integer('app_roles');
                $table->string('description', 255)->nullable();
                $table->string('push_token', 255)->nullable();

                $table->index(['app_id', 'nip']);
                $table->index(['app_id', 'app_roles']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('sqlsrv')->dropIfExists('tb_master_nurse_supervision_approles');
        Schema::connection('sqlsrv')->dropIfExists('ref_master_nurse_supervision_approles');
    }
};

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
        if (! Schema::connection('sqlsrv')->hasTable('ref_master_nurse_supervision_role_permissions')) {
            Schema::connection('sqlsrv')->create('ref_master_nurse_supervision_role_permissions', function (Blueprint $table) {
                $table->id();
                $table->integer('app_id')->default(6);
                $table->integer('role_id');
                $table->string('permission_key', 50);
                $table->timestamps();

                $table->index(['app_id', 'role_id']);
                $table->unique(['app_id', 'role_id', 'permission_key'], 'unique_app_role_permission');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('sqlsrv')->dropIfExists('ref_master_nurse_supervision_role_permissions');
    }
};


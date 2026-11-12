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
        if (! Schema::connection('sqlsrv')->hasColumn('tb_nurse_supervision_incidents', 'status_change_count')) {
            Schema::connection('sqlsrv')->table('tb_nurse_supervision_incidents', function (Blueprint $table) {
                $table->unsignedTinyInteger('status_change_count')->default(0);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::connection('sqlsrv')->hasColumn('tb_nurse_supervision_incidents', 'status_change_count')) {
            Schema::connection('sqlsrv')->table('tb_nurse_supervision_incidents', function (Blueprint $table) {
                $table->dropColumn('status_change_count');
            });
        }
    }
};

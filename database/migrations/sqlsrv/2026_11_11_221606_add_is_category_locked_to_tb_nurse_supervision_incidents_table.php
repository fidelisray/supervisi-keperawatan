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
        if (! Schema::connection('sqlsrv')->hasColumn('tb_nurse_supervision_incidents', 'is_category_locked')) {
            Schema::connection('sqlsrv')->table('tb_nurse_supervision_incidents', function (Blueprint $table) {
                $table->boolean('is_category_locked')->default(false);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::connection('sqlsrv')->hasColumn('tb_nurse_supervision_incidents', 'is_category_locked')) {
            Schema::connection('sqlsrv')->table('tb_nurse_supervision_incidents', function (Blueprint $table) {
                $table->dropColumn('is_category_locked');
            });
        }
    }
};

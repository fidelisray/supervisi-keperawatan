<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
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
        if (! Schema::connection('sqlsrv')->hasTable('tb_nurse_supervision_incidents')) {
            return;
        }

        $driver = DB::connection('sqlsrv')->getDriverName();

        if ($driver === 'sqlsrv') {
            DB::connection('sqlsrv')->statement("
                DECLARE @ConstraintName nvarchar(200);
                SELECT @ConstraintName = dc.name
                FROM sys.default_constraints dc
                INNER JOIN sys.columns c ON dc.parent_object_id = c.object_id AND dc.parent_column_id = c.column_id
                WHERE dc.parent_object_id = OBJECT_ID('tb_nurse_supervision_incidents')
                  AND c.name = 'status';
                IF @ConstraintName IS NOT NULL
                    EXEC('ALTER TABLE [tb_nurse_supervision_incidents] DROP CONSTRAINT [' + @ConstraintName + ']');
            ");

            DB::connection('sqlsrv')->statement(
                'ALTER TABLE [tb_nurse_supervision_incidents] ALTER COLUMN [status] NVARCHAR(255) NULL'
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::connection('sqlsrv')->hasTable('tb_nurse_supervision_incidents')) {
            return;
        }

        DB::connection('sqlsrv')
            ->table('tb_nurse_supervision_incidents')
            ->whereNull('status')
            ->update(['status' => 'in_progress']);

        $driver = DB::connection('sqlsrv')->getDriverName();

        if ($driver === 'sqlsrv') {
            DB::connection('sqlsrv')->statement(
                'ALTER TABLE [tb_nurse_supervision_incidents] ALTER COLUMN [status] NVARCHAR(255) NOT NULL'
            );

            DB::connection('sqlsrv')->statement(
                "ALTER TABLE [tb_nurse_supervision_incidents] ADD CONSTRAINT [DF_tb_nurse_supervision_incidents_status] DEFAULT 'in_progress' FOR [status]"
            );
        }
    }
};


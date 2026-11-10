<?php

use App\Models\SupervisionReport;
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
        if (! Schema::connection('sqlsrv')->hasTable('tb_nurse_supervision_reports')) {
            return;
        }

        $driver = DB::connection('sqlsrv')->getDriverName();

        if ($driver === 'sqlsrv') {
            DB::connection('sqlsrv')->statement(
                'ALTER TABLE [tb_nurse_supervision_reports] ALTER COLUMN [nurse_in_charge] NVARCHAR(MAX) NULL'
            );
        }

        $rows = DB::connection('sqlsrv')
            ->table('tb_nurse_supervision_reports')
            ->select(['id', 'nurse_in_charge'])
            ->get();

        foreach ($rows as $row) {
            $raw = $row->nurse_in_charge;
            $normalizedList = SupervisionReport::formatCollaborators($raw);
            $encoded = json_encode($normalizedList, JSON_UNESCAPED_UNICODE) ?: '[]';

            if ($raw !== $encoded) {
                DB::connection('sqlsrv')
                    ->table('tb_nurse_supervision_reports')
                    ->where('id', $row->id)
                    ->update(['nurse_in_charge' => $encoded]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::connection('sqlsrv')->hasTable('tb_nurse_supervision_reports')) {
            return;
        }

        $rows = DB::connection('sqlsrv')
            ->table('tb_nurse_supervision_reports')
            ->select(['id', 'nurse_in_charge'])
            ->get();

        foreach ($rows as $row) {
            $normalizedList = SupervisionReport::formatCollaborators($row->nurse_in_charge);
            $legacyString = mb_substr(implode(' | ', $normalizedList), 0, 255);

            DB::connection('sqlsrv')
                ->table('tb_nurse_supervision_reports')
                ->where('id', $row->id)
                ->update(['nurse_in_charge' => $legacyString]);
        }

        $driver = DB::connection('sqlsrv')->getDriverName();

        if ($driver === 'sqlsrv') {
            DB::connection('sqlsrv')->statement(
                'ALTER TABLE [tb_nurse_supervision_reports] ALTER COLUMN [nurse_in_charge] NVARCHAR(255) NOT NULL'
            );
        }
    }
};

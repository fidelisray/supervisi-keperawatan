<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        $schema = Schema::connection('sqlsrv');
        $connection = DB::connection('sqlsrv');

        if (! $schema->hasTable('tb_master_nurse_supervision_incidents_category')) {
            $schema->create('tb_master_nurse_supervision_incidents_category', function (Blueprint $table): void {
                $table->id();
                $table->string('incident_category', 150);
                $table->text('description')->nullable();
                $table->string('created_by', 50)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->string('updated_by', 50)->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->string('deleted_by', 50)->nullable();
                $table->timestamp('deleted_at')->nullable();
                $table->string('is_active', 10)->default('true');

                $table->index(['is_active', 'deleted_at']);
            });
        }

        if ($schema->hasTable('tb_nurse_supervision_incidents')) {
            $connection->table('tb_nurse_supervision_incidents')->delete();
        }

        if ($schema->hasTable('tb_nurse_supervision_reports')) {
            $connection->table('tb_nurse_supervision_reports')->delete();
        }

        if ($schema->hasTable('tb_nurse_supervision_incidents')) {
            $schema->dropIfExists('tb_nurse_supervision_incidents');

            $schema->create('tb_nurse_supervision_incidents', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('supervision_report_id')
                    ->constrained('tb_nurse_supervision_reports')
                    ->cascadeOnDelete();
                $table->foreignId('category')
                    ->constrained('tb_master_nurse_supervision_incidents_category');
                $table->string('incident_time')->nullable();
                $table->text('description');
                $table->text('action_taken')->nullable();
                // $table->string('status')->default('in_progress');
                $table->string('status')->nullable();
                $table->timestamps();

                $table->index(['supervision_report_id', 'category']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $schema = Schema::connection('sqlsrv');

        if ($schema->hasTable('tb_nurse_supervision_incidents')) {
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
                // $table->string('status')->default('in_progress');
                $table->string('status')->nullable();
                $table->timestamps();

                $table->index(['supervision_report_id', 'category']);
            });
        }

        $schema->dropIfExists('tb_master_nurse_supervision_incidents_category');
    }
};

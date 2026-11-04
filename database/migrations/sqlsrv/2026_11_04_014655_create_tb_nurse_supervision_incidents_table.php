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
        if (! Schema::connection('sqlsrv')->hasTable('tb_nurse_supervision_incidents')) {
            Schema::connection('sqlsrv')->create('tb_nurse_supervision_incidents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supervision_report_id')
                    ->constrained('tb_nurse_supervision_reports')
                    ->cascadeOnDelete();
                $table->string('category'); // complain, fasilitas, jkn_6_hari, lain_lain
                $table->string('incident_time')->nullable(); // e.g., 09:45
                $table->text('description');
                $table->text('action_taken')->nullable();
                $table->string('status')->default('in_progress'); // in_progress, resolved, escalated
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
        Schema::connection('sqlsrv')->dropIfExists('tb_nurse_supervision_incidents');
    }
};


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
        if (! Schema::connection('sqlsrv')->hasTable('tb_nurse_supervision_reports')) {
            Schema::connection('sqlsrv')->create('tb_nurse_supervision_reports', function (Blueprint $table) {
                $table->id();
                $table->date('report_date');
                $table->string('shift'); // pagi, sore, malam
                $table->string('room_name');
                $table->unsignedInteger('patient_count')->default(0);
                $table->unsignedInteger('nurse_count')->default(0);
                $table->string('supervisor_name');
                // $table->string('nurse_in_charge');
                $table->json('nurse_in_charge')->nullable();
                $table->text('general_notes')->nullable();
                $table->string('status')->default('submitted'); // draft, submitted, verified
                $table->timestamps();

                $table->index(['report_date', 'shift', 'room_name']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('sqlsrv')->dropIfExists('tb_nurse_supervision_reports');
    }
};

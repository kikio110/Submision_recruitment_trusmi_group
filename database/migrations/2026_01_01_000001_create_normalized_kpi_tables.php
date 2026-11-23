<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Soal #5 - Normalisasi table_kpi_marketing.
 *
 * Tabel asli (flat) mengulang nama karyawan & nama KPI di setiap baris, serta
 * tidak menyimpan aturan KPI (target, bobot, penalti late) di database.
 * Hasil normalisasi (3NF):
 *   employees       -> master karyawan
 *   kpi_categories  -> master KPI + target, bobot, penalti late
 *   tasklists       -> transaksi tasklist (FK ke employees & kpi_categories)
 *
 * VIEW `table_kpi_marketing` dibuat agar query SQL soal #1 dan #2 dapat
 * dijalankan APA ADANYA seperti pada struktur tabel lama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $t) {
            $t->id();
            $t->string('name', 100)->unique();
            $t->timestamps();
        });

        Schema::create('kpi_categories', function (Blueprint $t) {
            $t->id();
            $t->string('name', 100)->unique();      // Sales / Report
            $t->unsignedSmallInteger('target');     // target KPI
            $t->decimal('weight', 5, 4);            // bobot KPI (0.50)
            $t->decimal('late_penalty', 5, 4);      // pengurangan per late (0.07 / 0.05)
            $t->timestamps();
        });

        Schema::create('tasklists', function (Blueprint $t) {
            $t->id();
            $t->string('name', 100);
            $t->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $t->foreignId('kpi_category_id')->constrained('kpi_categories')->restrictOnDelete();
            $t->date('deadline')->nullable();
            $t->date('actual_date')->nullable();
            $t->timestamps();

            $t->index('employee_id');
            $t->index('kpi_category_id');
        });

        DB::statement("
            CREATE VIEW table_kpi_marketing AS
            SELECT t.id          AS id,
                   t.name        AS tasklist,
                   c.name        AS kpi,
                   e.name        AS karyawan,
                   t.deadline    AS deadline,
                   t.actual_date AS aktual
            FROM tasklists t
            JOIN kpi_categories c ON c.id = t.kpi_category_id
            JOIN employees e      ON e.id = t.employee_id
        ");
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS table_kpi_marketing');
        Schema::dropIfExists('tasklists');
        Schema::dropIfExists('kpi_categories');
        Schema::dropIfExists('employees');
    }
};

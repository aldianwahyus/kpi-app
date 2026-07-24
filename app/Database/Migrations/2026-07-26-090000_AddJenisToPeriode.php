<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Jenis Periode menentukan rentang bulan yang dipakai untuk menghitung
 * Target efektif dari Master Target: Bulanan = 1 bulan, Triwulan = 3 bulan,
 * Semester = 6 bulan, Tahunan = 12 bulan.
 */
class AddJenisToPeriode extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('periode', [
            'jenis' => [
                'type'       => 'ENUM',
                'constraint' => ['bulanan', 'triwulan', 'semester', 'tahunan'],
                'default'    => 'bulanan',
                'null'       => false,
                'after'      => 'kode',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('periode', 'jenis');
    }
}

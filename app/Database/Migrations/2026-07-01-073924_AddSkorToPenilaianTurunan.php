<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSkorToPenilaianTurunan extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('penilaian_turunan', [
            'skor' => [
                'type'       => 'DECIMAL',
                'constraint' => '8,2',
                'null'       => true,
                'default'    => null,
                'after'      => 'realisasi',
            ],
            'nilai_kontribusi' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,4',
                'null'       => true,
                'default'    => null,
                'after'      => 'skor',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('penilaian_turunan', 'skor');
        $this->forge->dropColumn('penilaian_turunan', 'nilai_kontribusi');
    }
}
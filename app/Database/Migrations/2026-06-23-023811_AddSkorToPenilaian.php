<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

class AddSkorToPenilaian extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('penilaian', [
            'skor' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'null'       => true,
                'after'      => 'realisasi',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('penilaian', 'skor');
    }
}
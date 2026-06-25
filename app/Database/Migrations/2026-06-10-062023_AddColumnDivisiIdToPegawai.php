<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

class AddColumnDivisiIdToPegawai extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('pegawai', [
            'divisi_id' => [
                'type'       => 'INT',
                'null'       => true,
                'after'      => 'unit',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('pegawai', 'divisi_id');
    }
}
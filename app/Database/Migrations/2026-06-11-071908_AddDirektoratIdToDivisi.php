<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

class AddDirektoratIdToDivisi extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('divisi', [
            'direktorat_id' => [
                'type'  => 'INT',
                'null'  => true,
                'after' => 'kode',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('divisi', 'direktorat_id');
    }
}
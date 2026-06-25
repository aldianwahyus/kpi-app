<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

class CreateTableDraftUlangRequest extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'auto_increment' => true],
            'tipe'            => ['type' => 'ENUM', 'constraint' => ['pegawai','periode']],
            'pegawai_id'      => ['type' => 'INT', 'null' => true],
            'periode_id'      => ['type' => 'INT'],
            'alasan'          => ['type' => 'TEXT'],
            'status'          => ['type' => 'ENUM', 'constraint' => ['pending','dikonfirmasi','ditolak'], 'default' => 'pending'],
            'requested_by'    => ['type' => 'INT'],
            'requested_by_nama' => ['type' => 'VARCHAR', 'constraint' => 100],
            'requested_at'    => ['type' => 'DATETIME'],
            'confirmed_by'    => ['type' => 'INT', 'null' => true],
            'confirmed_by_nama' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'confirmed_at'    => ['type' => 'DATETIME', 'null' => true],
            'catatan_admin'   => ['type' => 'TEXT', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('status');
        $this->forge->addKey('periode_id');
        $this->forge->createTable('draft_ulang_request');
    }

    public function down(): void
    {
        $this->forge->dropTable('draft_ulang_request');
    }
}
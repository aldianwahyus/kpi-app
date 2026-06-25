<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

class CreateTableAuditLog extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'auto_increment' => true],
            'table_name'   => ['type' => 'VARCHAR', 'constraint' => 50],
            'record_id'    => ['type' => 'INT'],
            'action'       => ['type' => 'VARCHAR', 'constraint' => 30],
            'user_id'      => ['type' => 'INT', 'null' => true],
            'user_nama'    => ['type' => 'VARCHAR', 'constraint' => 100],
            'user_jabatan' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'user_email'   => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'old_value'    => ['type' => 'JSON', 'null' => true],
            'new_value'    => ['type' => 'JSON', 'null' => true],
            'keterangan'   => ['type' => 'TEXT', 'null' => true],
            'ip_address'   => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['table_name', 'record_id']);
        $this->forge->addKey('user_id');
        $this->forge->createTable('audit_log');
    }

    public function down(): void
    {
        $this->forge->dropTable('audit_log');
    }
}
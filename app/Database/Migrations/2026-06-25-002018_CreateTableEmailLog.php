<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

class CreateTableEmailLog extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'auto_increment' => true],
            'jenis'        => ['type' => 'VARCHAR', 'constraint' => 30],
            'to_email'     => ['type' => 'VARCHAR', 'constraint' => 100],
            'to_nama'      => ['type' => 'VARCHAR', 'constraint' => 100],
            'subject'      => ['type' => 'VARCHAR', 'constraint' => 200],
            'status'       => ['type' => 'ENUM', 'constraint' => ['terkirim','gagal'], 'default' => 'gagal'],
            'error_message'=> ['type' => 'TEXT', 'null' => true],
            'periode_id'   => ['type' => 'INT', 'null' => true],
            'sent_by'      => ['type' => 'INT', 'null' => true],
            'sent_by_nama' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('to_email');
        $this->forge->addKey('status');
        $this->forge->addKey('created_at');
        $this->forge->createTable('email_log');
    }

    public function down(): void
    {
        $this->forge->dropTable('email_log');
    }
}
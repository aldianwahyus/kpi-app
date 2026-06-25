<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

class CreateTableMenuPermission extends Migration
{
    public function up(): void
    {
        // Daftar menu/fitur yang bisa diatur
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'auto_increment' => true],
            'kode_menu'    => ['type' => 'VARCHAR', 'constraint' => 50],
            'nama_menu'    => ['type' => 'VARCHAR', 'constraint' => 100],
            'grup'         => ['type' => 'VARCHAR', 'constraint' => 50],
            'urutan'       => ['type' => 'INT', 'default' => 0],
            'is_active'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('kode_menu');
        $this->forge->createTable('menu_list');

        // Relasi role <-> menu (permission)
        $this->forge->addField([
            'id'        => ['type' => 'INT', 'auto_increment' => true],
            'role'      => ['type' => 'VARCHAR', 'constraint' => 30],
            'menu_id'   => ['type' => 'INT'],
            'can_view'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'can_edit'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['role', 'menu_id']);
        $this->forge->createTable('role_permission');
    }

    public function down(): void
    {
        $this->forge->dropTable('role_permission');
        $this->forge->dropTable('menu_list');
    }
}
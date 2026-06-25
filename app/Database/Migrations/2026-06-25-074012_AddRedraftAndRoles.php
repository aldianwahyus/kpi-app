<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

class AddRedraftAndRoles extends Migration
{
    public function up()
    {
        // 1. Ubah struktur ENUM pada tabel users
        $this->db->query("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'hr', 'drafter', 'approver', 'pegawai') NOT NULL");

        // Opsional: Otomatis memindahkan user lama ke role baru
        $this->db->query("UPDATE users SET role = 'drafter' WHERE role = 'kepala_unit'");
        $this->db->query("UPDATE users SET role = 'approver' WHERE role = 'manajer'");

        // 2. Tambah kolom flag redraft di tabel penilaian
        $this->forge->addColumn('penilaian', [
            'is_redraft_requested' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'redraft_requested_by' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('penilaian', ['is_redraft_requested', 'redraft_requested_by']);
    }
}
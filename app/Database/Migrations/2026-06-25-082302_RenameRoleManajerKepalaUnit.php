<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

class RenameRoleManajerKepalaUnit extends Migration
{
    public function up(): void
    {
        // Update enum role
        $this->db->query("ALTER TABLE users MODIFY COLUMN role
            ENUM('admin','hr','drafter','approver','pegawai')
            DEFAULT 'pegawai'");

        // Migrasi data existing: manajer -> drafter, kepala_unit -> approver
        $this->db->query("UPDATE users SET role = 'drafter' WHERE role = 'manajer'");
        $this->db->query("UPDATE users SET role = 'approver' WHERE role = 'kepala_unit'");
    }

    public function down(): void
    {
        $this->db->query("UPDATE users SET role = 'manajer' WHERE role = 'drafter'");
        $this->db->query("UPDATE users SET role = 'kepala_unit' WHERE role = 'approver'");
        $this->db->query("ALTER TABLE users MODIFY COLUMN role
            ENUM('admin','hr','manajer','kepala_unit','pegawai')
            DEFAULT 'pegawai'");
    }
}
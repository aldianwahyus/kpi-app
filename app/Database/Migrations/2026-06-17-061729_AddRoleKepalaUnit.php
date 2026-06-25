<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

class AddRoleKepalaUnit extends Migration
{
    public function up(): void
    {
        $this->db->query("ALTER TABLE users MODIFY COLUMN role
            ENUM('admin','hr','manajer','kepala_unit','pegawai')
            DEFAULT 'pegawai'");
    }

    public function down(): void
    {
        $this->db->query("ALTER TABLE users MODIFY COLUMN role
            ENUM('admin','hr','manajer','pegawai')
            DEFAULT 'pegawai'");
    }
}
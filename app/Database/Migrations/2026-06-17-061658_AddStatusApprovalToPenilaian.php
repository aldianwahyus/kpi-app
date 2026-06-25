<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

class AddStatusApprovalToPenilaian extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('penilaian', [
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['draft','submitted','approved','rejected'],
                'default'    => 'draft',
                'after'      => 'nilai_kontribusi',
            ],
            'submitted_at' => [
                'type'  => 'DATETIME',
                'null'  => true,
                'after' => 'status',
            ],
            'approved_by' => [
                'type'  => 'INT',
                'null'  => true,
                'after' => 'submitted_at',
            ],
            'approved_at' => [
                'type'  => 'DATETIME',
                'null'  => true,
                'after' => 'approved_by',
            ],
            'reject_note' => [
                'type'  => 'TEXT',
                'null'  => true,
                'after' => 'approved_at',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('penilaian', 'status');
        $this->forge->dropColumn('penilaian', 'submitted_at');
        $this->forge->dropColumn('penilaian', 'approved_by');
        $this->forge->dropColumn('penilaian', 'approved_at');
        $this->forge->dropColumn('penilaian', 'reject_note');
    }
}
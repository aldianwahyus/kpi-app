<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

class AddMustChangePasswordToUsers extends Migration
{
    public function up(): void
    {
        $this->forge->addColumn('users', [
            'must_change_password' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
                'after'      => 'password',
            ],
        ]);
    }

    public function down(): void
    {
        $this->forge->dropColumn('users', 'must_change_password');
    }
}
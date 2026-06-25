<?php
namespace App\Database\Seeds;
use CodeIgniter\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');
        $users = [
            [
                'nama'       => 'Administrator',
                'email'      => 'admin@kpi.local',
                'password'   => password_hash('admin123', PASSWORD_DEFAULT),
                'role'       => 'admin',
                'is_active'  => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'nama'       => 'HR Manager',
                'email'      => 'hr@kpi.local',
                'password'   => password_hash('hr123', PASSWORD_DEFAULT),
                'role'       => 'hr',
                'is_active'  => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];
        $this->db->table('users')->insertBatch($users);
        echo "Users seeded: " . count($users) . " records.\n";
    }
}
<?php
namespace App\Database\Seeds;
use CodeIgniter\Database\Seeder;

class DivisiSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');
        $data = [
            ['kode'=>'DIV-HR',  'nama'=>'Human Resources',      'deskripsi'=>'Divisi pengelolaan SDM',          'is_active'=>1,'created_at'=>$now,'updated_at'=>$now],
            ['kode'=>'DIV-FIN', 'nama'=>'Finance & Accounting',  'deskripsi'=>'Divisi keuangan dan akuntansi',   'is_active'=>1,'created_at'=>$now,'updated_at'=>$now],
            ['kode'=>'DIV-IT',  'nama'=>'Information Technology','deskripsi'=>'Divisi teknologi informasi',      'is_active'=>1,'created_at'=>$now,'updated_at'=>$now],
            ['kode'=>'DIV-OPS', 'nama'=>'Operations',            'deskripsi'=>'Divisi operasional',              'is_active'=>1,'created_at'=>$now,'updated_at'=>$now],
        ];
        $this->db->table('divisi')->insertBatch($data);
        echo "Divisi seeded: " . count($data) . " records.\n";
    }
}
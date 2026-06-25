<?php
namespace App\Database\Seeds;
use CodeIgniter\Database\Seeder;

class DirektoratSeeder extends Seeder
{
    public function run(): void
    {
        $now  = date('Y-m-d H:i:s');
        $data = [
            [
                'kode'       => 'DIR-UTAMA',
                'nama'       => 'Direktorat Utama',
                'singkatan'  => 'DIR-UTAMA',
                'deskripsi'  => 'Direktorat Utama mencakup unit Perencanaan Strategis, IT, Audit Internal, Sekretariat, dan unit pendukung lainnya',
                'is_active'  => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'kode'       => 'DIR-DJ',
                'nama'       => 'Direktorat Dana dan Transaksi',
                'singkatan'  => 'DIR-DJ',
                'deskripsi'  => 'Direktorat Dana dan Transaksi mencakup unit Dana Korporasi, Dana Ritel, dan E-Channel',
                'is_active'  => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'kode'       => 'DIR-KEU-OPS',
                'nama'       => 'Direktorat Keuangan dan Operasional',
                'singkatan'  => 'DIR-KEU-OPS',
                'deskripsi'  => 'Direktorat Keuangan dan Operasional mencakup unit Keuangan, Operasional, Pengadaan, dan Pembiayaan',
                'is_active'  => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'kode'       => 'DIR-PBY',
                'nama'       => 'Direktorat Pembiayaan',
                'singkatan'  => 'DIR-PBY',
                'deskripsi'  => 'Direktorat Pembiayaan mencakup unit Pembiayaan Produktif, Konsumer, dan Remedial',
                'is_active'  => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'kode'       => 'DIR-MANRISK',
                'nama'       => 'Direktorat Kepatuhan dan Manajemen Risiko',
                'singkatan'  => 'DIR-MANRISK',
                'deskripsi'  => 'Direktorat Kepatuhan dan Manajemen Risiko mencakup unit Kepatuhan, Hukum, Anti Fraud, Manajemen Risiko, dan BPP/SOP',
                'is_active'  => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        $this->db->table('direktorat')->insertBatch($data);
        echo "Direktorat seeded: " . count($data) . " records.\n";
    }
}
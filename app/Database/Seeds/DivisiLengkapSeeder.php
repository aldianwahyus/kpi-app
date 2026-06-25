<?php
namespace App\Database\Seeds;
use CodeIgniter\Database\Seeder;

class DivisiLengkapSeeder extends Seeder
{
    public function run(): void
    {
        // Hapus data lama
        $this->db->table('divisi')->truncate();

        $now  = date('Y-m-d H:i:s');

        // Ambil ID direktorat
        $dirs = [];
        $rows = $this->db->table('direktorat')->get()->getResultArray();
        foreach ($rows as $r) {
            $dirs[$r['kode']] = $r['id'];
        }

        $data = [
            // ── Direktorat Dana dan Transaksi ──────────────────────
            [
                'kode'          => 'DIV-TIB',
                'direktorat_id' => $dirs['DIR-DJ'],
                'nama'          => 'Divisi Treasury & Institusional Banking',
                'deskripsi'     => 'Divisi Treasury & Institusional Banking',
                'is_active'     => 1,
            ],
            [
                'kode'          => 'DIV-FT',
                'direktorat_id' => $dirs['DIR-DJ'],
                'nama'          => 'Divisi Funding & Transaction',
                'deskripsi'     => 'Divisi Funding & Transaction',
                'is_active'     => 1,
            ],

            // ── Direktorat Kepatuhan dan Manajemen Risiko ──────────
            [
                'kode'          => 'DIV-HCD',
                'direktorat_id' => $dirs['DIR-MANRISK'],
                'nama'          => 'Divisi Human Capital & Development',
                'deskripsi'     => 'Divisi Human Capital & Development',
                'is_active'     => 1,
            ],
            [
                'kode'          => 'DIV-COMP',
                'direktorat_id' => $dirs['DIR-MANRISK'],
                'nama'          => 'Divisi Compliance',
                'deskripsi'     => 'Divisi Compliance',
                'is_active'     => 1,
            ],
            [
                'kode'          => 'DESK-LEGAL',
                'direktorat_id' => $dirs['DIR-MANRISK'],
                'nama'          => 'Desk Legal',
                'deskripsi'     => 'Desk Legal',
                'is_active'     => 1,
            ],
            [
                'kode'          => 'DIV-RM',
                'direktorat_id' => $dirs['DIR-MANRISK'],
                'nama'          => 'Divisi Risk Management',
                'deskripsi'     => 'Divisi Risk Management',
                'is_active'     => 1,
            ],
            [
                'kode'          => 'DESK-PP',
                'direktorat_id' => $dirs['DIR-MANRISK'],
                'nama'          => 'Desk Policy & Procedure',
                'deskripsi'     => 'Desk Policy & Procedure',
                'is_active'     => 1,
            ],

            // ── Direktorat Keuangan dan Operasional ────────────────
            [
                'kode'          => 'DIV-OPS',
                'direktorat_id' => $dirs['DIR-KEU-OPS'],
                'nama'          => 'Divisi Operation',
                'deskripsi'     => 'Divisi Operation',
                'is_active'     => 1,
            ],
            [
                'kode'          => 'DESK-FS',
                'direktorat_id' => $dirs['DIR-KEU-OPS'],
                'nama'          => 'Desk Financing Support',
                'deskripsi'     => 'Desk Financing Support',
                'is_active'     => 1,
            ],
            [
                'kode'          => 'DIV-BR',
                'direktorat_id' => $dirs['DIR-KEU-OPS'],
                'nama'          => 'Divisi Business Risk',
                'deskripsi'     => 'Divisi Business Risk',
                'is_active'     => 1,
            ],
            [
                'kode'          => 'DIV-NL',
                'direktorat_id' => $dirs['DIR-KEU-OPS'],
                'nama'          => 'Divisi Network & Logistic',
                'deskripsi'     => 'Divisi Network & Logistic',
                'is_active'     => 1,
            ],
            [
                'kode'          => 'DIV-AFC',
                'direktorat_id' => $dirs['DIR-KEU-OPS'],
                'nama'          => 'Divisi Accounting & Financial Control',
                'deskripsi'     => 'Divisi Accounting & Financial Control',
                'is_active'     => 1,
            ],

            // ── Direktorat Pembiayaan ──────────────────────────────
            [
                'kode'          => 'SEVP-BISNIS',
                'direktorat_id' => $dirs['DIR-PBY'],
                'nama'          => 'SEVP Bisnis',
                'deskripsi'     => 'SEVP Bisnis',
                'is_active'     => 1,
            ],
            [
                'kode'          => 'DIV-CB',
                'direktorat_id' => $dirs['DIR-PBY'],
                'nama'          => 'Divisi Commercial Banking',
                'deskripsi'     => 'Divisi Commercial Banking',
                'is_active'     => 1,
            ],
            [
                'kode'          => 'DIV-CONS',
                'direktorat_id' => $dirs['DIR-PBY'],
                'nama'          => 'Divisi Consumer Banking',
                'deskripsi'     => 'Divisi Consumer Banking',
                'is_active'     => 1,
            ],

            // ── Direktorat Utama ───────────────────────────────────
            [
                'kode'          => 'DIV-CSP',
                'direktorat_id' => $dirs['DIR-UTAMA'],
                'nama'          => 'Divisi Corporate Strategic & Planning',
                'deskripsi'     => 'Divisi Corporate Strategic & Planning',
                'is_active'     => 1,
            ],
            [
                'kode'          => 'DESK-TO',
                'direktorat_id' => $dirs['DIR-UTAMA'],
                'nama'          => 'Desk Transformation Office',
                'deskripsi'     => 'Desk Transformation Office',
                'is_active'     => 1,
            ],
            [
                'kode'          => 'DESK-CS',
                'direktorat_id' => $dirs['DIR-UTAMA'],
                'nama'          => 'Desk Corporate Secretary',
                'deskripsi'     => 'Desk Corporate Secretary',
                'is_active'     => 1,
            ],
            [
                'kode'          => 'DIV-IA',
                'direktorat_id' => $dirs['DIR-UTAMA'],
                'nama'          => 'Divisi Internal Audit',
                'deskripsi'     => 'Divisi Internal Audit',
                'is_active'     => 1,
            ],
            [
                'kode'          => 'EAD',
                'direktorat_id' => $dirs['DIR-UTAMA'],
                'nama'          => 'Executive Assistant Director',
                'deskripsi'     => 'Executive Assistant Director',
                'is_active'     => 1,
            ],
            [
                'kode'          => 'SEVP-IT',
                'direktorat_id' => $dirs['DIR-UTAMA'],
                'nama'          => 'SEVP IT',
                'deskripsi'     => 'SEVP IT',
                'is_active'     => 1,
            ],
            [
                'kode'          => 'DIV-ITO',
                'direktorat_id' => $dirs['DIR-UTAMA'],
                'nama'          => 'Divisi Information Technology Operation',
                'deskripsi'     => 'Divisi Information Technology Operation',
                'is_active'     => 1,
            ],
            [
                'kode'          => 'DIV-TD',
                'direktorat_id' => $dirs['DIR-UTAMA'],
                'nama'          => 'Divisi Technology Development',
                'deskripsi'     => 'Divisi Technology Development',
                'is_active'     => 1,
            ],
            [
                'kode'          => 'DESK-CISO',
                'direktorat_id' => $dirs['DIR-UTAMA'],
                'nama'          => 'Desk CISO',
                'deskripsi'     => 'Desk Chief Information Security Officer',
                'is_active'     => 1,
            ],
        ];

        foreach ($data as &$row) {
            $row['created_at'] = $now;
            $row['updated_at'] = $now;
        }

        $this->db->table('divisi')->insertBatch($data);
        echo "Divisi seeded: " . count($data) . " records.\n";
    }
}
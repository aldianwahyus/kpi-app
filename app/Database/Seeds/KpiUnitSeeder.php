<?php
namespace App\Database\Seeds;
use CodeIgniter\Database\Seeder;

class KpiUnitSeeder extends Seeder
{
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        // Ambil ID direktorat
        $dirs = [];
        $rows = $this->db->table('direktorat')->get()->getResultArray();
        foreach ($rows as $r) {
            $dirs[$r['kode']] = $r['id'];
        }

        $data = [
            // ══ DIREKTORAT UTAMA ══════════════════════════════════
            ['dir'=>'DIR-UTAMA','perspektif'=>'Financial',        'kode'=>'DU-F1',  'nama'=>'Rasio ROA',                                    'satuan'=>'%',     'polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-UTAMA','perspektif'=>'Financial',        'kode'=>'DU-F2',  'nama'=>'Ekspansi Pembiayaan',                          'satuan'=>'Juta Rp','polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-UTAMA','perspektif'=>'Financial',        'kode'=>'DU-F3',  'nama'=>'Porsi Pembiayaan Produktif',                   'satuan'=>'%',     'polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-UTAMA','perspektif'=>'Financial',        'kode'=>'DU-F4',  'nama'=>'NPF Absolute',                                 'satuan'=>'Juta Rp','polarity'=>'min','perubahan'=>'neg'],
            ['dir'=>'DIR-UTAMA','perspektif'=>'Financial',        'kode'=>'DU-F5',  'nama'=>'Financing at Risk (FaR)',                       'satuan'=>'%',     'polarity'=>'min','perubahan'=>'neg'],
            ['dir'=>'DIR-UTAMA','perspektif'=>'Financial',        'kode'=>'DU-F6',  'nama'=>'Ekspansi DPK',                                 'satuan'=>'Juta Rp','polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-UTAMA','perspektif'=>'Financial',        'kode'=>'DU-F7',  'nama'=>'CASA',                                         'satuan'=>'%',     'polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-UTAMA','perspektif'=>'Financial',        'kode'=>'DU-F8',  'nama'=>'Laba Bersih',                                  'satuan'=>'Juta Rp','polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-UTAMA','perspektif'=>'Customer',         'kode'=>'DU-C1',  'nama'=>'Growth User/Merchant E-Channel',               'satuan'=>'%',     'polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-UTAMA','perspektif'=>'Customer',         'kode'=>'DU-C2',  'nama'=>'Jumlah Pengaduan Nasabah',                     'satuan'=>'Jumlah','polarity'=>'min','perubahan'=>'neg'],
            ['dir'=>'DIR-UTAMA','perspektif'=>'Customer',         'kode'=>'DU-C3',  'nama'=>'Tingkat Kesehatan Bank',                       'satuan'=>'Skor',  'polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-UTAMA','perspektif'=>'Internal Process', 'kode'=>'DU-IP1', 'nama'=>'Penilaian GCG',                                'satuan'=>'Skor',  'polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-UTAMA','perspektif'=>'Internal Process', 'kode'=>'DU-IP2', 'nama'=>'Penyelesaian Temuan Audit',                    'satuan'=>'SLA',   'polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-UTAMA','perspektif'=>'Internal Process', 'kode'=>'DU-IP3', 'nama'=>'Pelaksanaan Evaluasi Kinerja',                 'satuan'=>'%',     'polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-UTAMA','perspektif'=>'Internal Process', 'kode'=>'DU-IP4', 'nama'=>'Revisi Dokumen oleh Regulator',                'satuan'=>'Jumlah','polarity'=>'min','perubahan'=>'neg'],
            ['dir'=>'DIR-UTAMA','perspektif'=>'Internal Process', 'kode'=>'DU-IP5', 'nama'=>'Inisiasi Project Internal Unit',               'satuan'=>'Jumlah','polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-UTAMA','perspektif'=>'Internal Process', 'kode'=>'DU-IP6', 'nama'=>'Kajian Inisiatif',                             'satuan'=>'Jumlah','polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-UTAMA','perspektif'=>'Internal Process', 'kode'=>'DU-IP7', 'nama'=>'Pelaksanaan Riset',                            'satuan'=>'Jumlah','polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-UTAMA','perspektif'=>'Internal Process', 'kode'=>'DU-IP8', 'nama'=>'Budaya Keuangan Keberlanjutan',                'satuan'=>'Skor',  'polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-UTAMA','perspektif'=>'Internal Process', 'kode'=>'DU-IP9', 'nama'=>'Deviasi Anggaran Vs Realisasi',                'satuan'=>'%',     'polarity'=>'min','perubahan'=>'neg'],
            ['dir'=>'DIR-UTAMA','perspektif'=>'Learning & Growth','kode'=>'DU-LG1', 'nama'=>'Kompetensi Pegawai',                           'satuan'=>'%',     'polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-UTAMA','perspektif'=>'Learning & Growth','kode'=>'DU-LG2', 'nama'=>'Denda/sanksi',                                 'satuan'=>'Jumlah','polarity'=>'min','perubahan'=>'pos'],

            // ══ DIREKTORAT DANA DAN TRANSAKSI ════════════════════
            ['dir'=>'DIR-DJ','perspektif'=>'Financial',        'kode'=>'DJ-F1',  'nama'=>'Ekspansi DPK',                                 'satuan'=>'Juta Rp','polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-DJ','perspektif'=>'Financial',        'kode'=>'DJ-F2',  'nama'=>'Cost of Fund (CoF)',                            'satuan'=>'%',     'polarity'=>'min','perubahan'=>'neg'],
            ['dir'=>'DIR-DJ','perspektif'=>'Financial',        'kode'=>'DJ-F3',  'nama'=>'CASA',                                         'satuan'=>'%',     'polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-DJ','perspektif'=>'Financial',        'kode'=>'DJ-F4',  'nama'=>'Laba Bersih',                                  'satuan'=>'Juta Rp','polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-DJ','perspektif'=>'Financial',        'kode'=>'DJ-F5',  'nama'=>'Fee Based Income',                             'satuan'=>'Juta Rp','polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-DJ','perspektif'=>'Financial',        'kode'=>'DJ-F6',  'nama'=>'Pendapatan Penempatan TRS',                    'satuan'=>'Juta Rp','polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-DJ','perspektif'=>'Financial',        'kode'=>'DJ-F7',  'nama'=>'Rasio Likuiditas (LCR & NSFR)',                'satuan'=>'%',     'polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-DJ','perspektif'=>'Customer',         'kode'=>'DJ-C1',  'nama'=>'Growth NoA Nasabah Institusional',             'satuan'=>'Jumlah','polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-DJ','perspektif'=>'Customer',         'kode'=>'DJ-C2',  'nama'=>'Growth NoA Tabungan',                          'satuan'=>'Jumlah','polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-DJ','perspektif'=>'Customer',         'kode'=>'DJ-C3',  'nama'=>'Merchant QRIS',                                'satuan'=>'Jumlah','polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-DJ','perspektif'=>'Customer',         'kode'=>'DJ-C4',  'nama'=>'User Mobile Banking',                          'satuan'=>'Jumlah','polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-DJ','perspektif'=>'Customer',         'kode'=>'DJ-C5',  'nama'=>'Kepuasan Customer',                            'satuan'=>'Skor',  'polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-DJ','perspektif'=>'Internal Process', 'kode'=>'DJ-IP1', 'nama'=>'Inisiasi Project Internal Unit',               'satuan'=>'Jumlah','polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-DJ','perspektif'=>'Internal Process', 'kode'=>'DJ-IP2', 'nama'=>'Penyelesaian Temuan Audit',                    'satuan'=>'SLA',   'polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-DJ','perspektif'=>'Internal Process', 'kode'=>'DJ-IP3', 'nama'=>'Pelaksanaan ALMA',                             'satuan'=>'%',     'polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-DJ','perspektif'=>'Learning & Growth','kode'=>'DJ-LG1', 'nama'=>'Kompetensi Pegawai',                           'satuan'=>'%',     'polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-DJ','perspektif'=>'Learning & Growth','kode'=>'DJ-LG2', 'nama'=>'Denda/sanksi',                                 'satuan'=>'Jumlah','polarity'=>'min','perubahan'=>'pos'],

            // ══ DIREKTORAT KEUANGAN DAN OPERASIONAL ══════════════
            ['dir'=>'DIR-KEU-OPS','perspektif'=>'Financial',        'kode'=>'KO-F1',  'nama'=>'Rasio ROA',                                'satuan'=>'%',     'polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-KEU-OPS','perspektif'=>'Financial',        'kode'=>'KO-F2',  'nama'=>'Laba Bersih',                              'satuan'=>'Juta Rp','polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-KEU-OPS','perspektif'=>'Financial',        'kode'=>'KO-F3',  'nama'=>'BOPO',                                     'satuan'=>'%',     'polarity'=>'min','perubahan'=>'neg'],
            ['dir'=>'DIR-KEU-OPS','perspektif'=>'Financial',        'kode'=>'KO-F4',  'nama'=>'Ekspansi Pembiayaan',                      'satuan'=>'Juta Rp','polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-KEU-OPS','perspektif'=>'Financial',        'kode'=>'KO-F5',  'nama'=>'NPF Absolute',                             'satuan'=>'Juta Rp','polarity'=>'min','perubahan'=>'neg'],
            ['dir'=>'DIR-KEU-OPS','perspektif'=>'Financial',        'kode'=>'KO-F6',  'nama'=>'Efisiensi Biaya Pengadaan',                'satuan'=>'%',     'polarity'=>'min','perubahan'=>'neg'],
            ['dir'=>'DIR-KEU-OPS','perspektif'=>'Financial',        'kode'=>'KO-F7',  'nama'=>'Rasio Overhead terhadap Pendapatan Operasional','satuan'=>'%','polarity'=>'min','perubahan'=>'neg'],
            ['dir'=>'DIR-KEU-OPS','perspektif'=>'Customer',         'kode'=>'KO-C1',  'nama'=>'Standar Layanan Cabang Binaan',            'satuan'=>'Skor',  'polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-KEU-OPS','perspektif'=>'Customer',         'kode'=>'KO-C2',  'nama'=>'Jumlah Pengaduan Nasabah Cabang Binaan',   'satuan'=>'Jumlah','polarity'=>'min','perubahan'=>'neg'],
            ['dir'=>'DIR-KEU-OPS','perspektif'=>'Customer',         'kode'=>'KO-C3',  'nama'=>'Ketepatan Laporan Keuangan',               'satuan'=>'SLA',   'polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-KEU-OPS','perspektif'=>'Customer',         'kode'=>'KO-C4',  'nama'=>'Realisasi Jaringan Kantor & E-Channel',    'satuan'=>'%',     'polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-KEU-OPS','perspektif'=>'Internal Process', 'kode'=>'KO-IP1', 'nama'=>'Inisiasi Project Internal Unit',           'satuan'=>'Jumlah','polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-KEU-OPS','perspektif'=>'Internal Process', 'kode'=>'KO-IP2', 'nama'=>'Penyelesaian Temuan Audit',                'satuan'=>'SLA',   'polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-KEU-OPS','perspektif'=>'Internal Process', 'kode'=>'KO-IP3', 'nama'=>'SLA Laporan Keuangan',                     'satuan'=>'SLA',   'polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-KEU-OPS','perspektif'=>'Internal Process', 'kode'=>'KO-IP4', 'nama'=>'SLA Proses Pengadaan',                     'satuan'=>'SLA',   'polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-KEU-OPS','perspektif'=>'Internal Process', 'kode'=>'KO-IP5', 'nama'=>'Rekonsiliasi Transaksi',                   'satuan'=>'%',     'polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-KEU-OPS','perspektif'=>'Internal Process', 'kode'=>'KO-IP6', 'nama'=>'Jumlah Kesalahan Transaksi',               'satuan'=>'Jumlah','polarity'=>'min','perubahan'=>'neg'],
            ['dir'=>'DIR-KEU-OPS','perspektif'=>'Internal Process', 'kode'=>'KO-IP7', 'nama'=>'Penyelesaian Aset Terbengkalai',           'satuan'=>'%',     'polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-KEU-OPS','perspektif'=>'Internal Process', 'kode'=>'KO-IP8', 'nama'=>'Optimalisasi Anggaran',                    'satuan'=>'%',     'polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-KEU-OPS','perspektif'=>'Internal Process', 'kode'=>'KO-IP9', 'nama'=>'Optimalisasi Pajak',                       'satuan'=>'%',     'polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-KEU-OPS','perspektif'=>'Learning & Growth','kode'=>'KO-LG1', 'nama'=>'Kompetensi Pegawai',                       'satuan'=>'%',     'polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-KEU-OPS','perspektif'=>'Learning & Growth','kode'=>'KO-LG2', 'nama'=>'Denda/sanksi',                             'satuan'=>'Jumlah','polarity'=>'min','perubahan'=>'pos'],

            // ══ DIREKTORAT PEMBIAYAAN ═════════════════════════════
            ['dir'=>'DIR-PBY','perspektif'=>'Financial',        'kode'=>'PBY-F1',  'nama'=>'Ekspansi Pembiayaan',                       'satuan'=>'Juta Rp','polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-PBY','perspektif'=>'Financial',        'kode'=>'PBY-F2',  'nama'=>'Ekspansi Pembiayaan UMKM',                  'satuan'=>'Juta Rp','polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-PBY','perspektif'=>'Financial',        'kode'=>'PBY-F3',  'nama'=>'Porsi Pembiayaan Produktif',                'satuan'=>'%',     'polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-PBY','perspektif'=>'Financial',        'kode'=>'PBY-F4',  'nama'=>'NPF Absolute',                              'satuan'=>'Juta Rp','polarity'=>'min','perubahan'=>'neg'],
            ['dir'=>'DIR-PBY','perspektif'=>'Financial',        'kode'=>'PBY-F5',  'nama'=>'Financing at Risk (FaR)',                    'satuan'=>'%',     'polarity'=>'min','perubahan'=>'neg'],
            ['dir'=>'DIR-PBY','perspektif'=>'Financial',        'kode'=>'PBY-F6',  'nama'=>'Laba Bersih',                               'satuan'=>'Juta Rp','polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-PBY','perspektif'=>'Financial',        'kode'=>'PBY-F7',  'nama'=>'NI (Net Imbalan)',                           'satuan'=>'%',     'polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-PBY','perspektif'=>'Financial',        'kode'=>'PBY-F8',  'nama'=>'Rasio NPF Gross',                           'satuan'=>'%',     'polarity'=>'min','perubahan'=>'neg'],
            ['dir'=>'DIR-PBY','perspektif'=>'Financial',        'kode'=>'PBY-F9',  'nama'=>'Absolute Kolektibilitas 2',                 'satuan'=>'Juta Rp','polarity'=>'min','perubahan'=>'neg'],
            ['dir'=>'DIR-PBY','perspektif'=>'Financial',        'kode'=>'PBY-F10', 'nama'=>'Recovery Hapus Buku',                       'satuan'=>'Juta Rp','polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-PBY','perspektif'=>'Customer',         'kode'=>'PBY-C1',  'nama'=>'Growth NoA Pembiayaan Produktif',           'satuan'=>'Jumlah','polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-PBY','perspektif'=>'Customer',         'kode'=>'PBY-C2',  'nama'=>'Growth NoA Pembiayaan Konsumer',            'satuan'=>'Jumlah','polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-PBY','perspektif'=>'Customer',         'kode'=>'PBY-C3',  'nama'=>'Jumlah Pengaduan Nasabah Remedial',         'satuan'=>'Jumlah','polarity'=>'min','perubahan'=>'neg'],
            ['dir'=>'DIR-PBY','perspektif'=>'Internal Process', 'kode'=>'PBY-IP1', 'nama'=>'Inisiasi Project Internal Unit',            'satuan'=>'Jumlah','polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-PBY','perspektif'=>'Internal Process', 'kode'=>'PBY-IP2', 'nama'=>'Penyelesaian Temuan Audit',                 'satuan'=>'SLA',   'polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-PBY','perspektif'=>'Internal Process', 'kode'=>'PBY-IP3', 'nama'=>'SLA Persetujuan Pembiayaan',                'satuan'=>'SLA',   'polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-PBY','perspektif'=>'Internal Process', 'kode'=>'PBY-IP4', 'nama'=>'Penyelesaian Hasil Penilaian Agunan',       'satuan'=>'%',     'polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-PBY','perspektif'=>'Internal Process', 'kode'=>'PBY-IP5', 'nama'=>'Penyelesaian Pencairan Pembiayaan',         'satuan'=>'%',     'polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-PBY','perspektif'=>'Internal Process', 'kode'=>'PBY-IP6', 'nama'=>'Kelengkapan Dokumen Pembiayaan',            'satuan'=>'%',     'polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-PBY','perspektif'=>'Internal Process', 'kode'=>'PBY-IP7', 'nama'=>'Kualitas Pembiayaan Restru',                'satuan'=>'%',     'polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-PBY','perspektif'=>'Internal Process', 'kode'=>'PBY-IP8', 'nama'=>'Lelang Agunan',                             'satuan'=>'Jumlah','polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-PBY','perspektif'=>'Learning & Growth','kode'=>'PBY-LG1', 'nama'=>'Kompetensi Pegawai',                        'satuan'=>'%',     'polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-PBY','perspektif'=>'Learning & Growth','kode'=>'PBY-LG2', 'nama'=>'Denda/sanksi',                              'satuan'=>'Jumlah','polarity'=>'min','perubahan'=>'pos'],

            // ══ DIREKTORAT KEPATUHAN DAN MANAJEMEN RISIKO ════════
            ['dir'=>'DIR-MANRISK','perspektif'=>'Financial',        'kode'=>'MR-F1',  'nama'=>'Laba Bersih',                             'satuan'=>'Juta Rp','polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-MANRISK','perspektif'=>'Financial',        'kode'=>'MR-F2',  'nama'=>'BOPO',                                    'satuan'=>'%',     'polarity'=>'min','perubahan'=>'neg'],
            ['dir'=>'DIR-MANRISK','perspektif'=>'Financial',        'kode'=>'MR-F3',  'nama'=>'Biaya Penanganan Sengketa Hukum',         'satuan'=>'Juta Rp','polarity'=>'min','perubahan'=>'neg'],
            ['dir'=>'DIR-MANRISK','perspektif'=>'Customer',         'kode'=>'MR-C1',  'nama'=>'Standar Layanan Cabang Binaan',           'satuan'=>'Skor',  'polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-MANRISK','perspektif'=>'Customer',         'kode'=>'MR-C2',  'nama'=>'Jumlah Pengaduan Nasabah',                'satuan'=>'Jumlah','polarity'=>'min','perubahan'=>'neg'],
            ['dir'=>'DIR-MANRISK','perspektif'=>'Customer',         'kode'=>'MR-C3',  'nama'=>'Tingkat Kesehatan Bank',                  'satuan'=>'Skor',  'polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-MANRISK','perspektif'=>'Internal Process', 'kode'=>'MR-IP1', 'nama'=>'Penilaian GCG',                           'satuan'=>'Skor',  'polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-MANRISK','perspektif'=>'Internal Process', 'kode'=>'MR-IP2', 'nama'=>'Rating FIR PPATK',                        'satuan'=>'Skor',  'polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-MANRISK','perspektif'=>'Internal Process', 'kode'=>'MR-IP3', 'nama'=>'Penyelesaian Temuan Audit',               'satuan'=>'SLA',   'polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-MANRISK','perspektif'=>'Internal Process', 'kode'=>'MR-IP4', 'nama'=>'SLA Opini dan/atau Kajian',               'satuan'=>'SLA',   'polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-MANRISK','perspektif'=>'Internal Process', 'kode'=>'MR-IP5', 'nama'=>'Surat terkait Perubahan Regulasi',        'satuan'=>'Jumlah','polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-MANRISK','perspektif'=>'Internal Process', 'kode'=>'MR-IP6', 'nama'=>'Sosialisasi Budaya Kepatuhan',            'satuan'=>'Jumlah','polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-MANRISK','perspektif'=>'Internal Process', 'kode'=>'MR-IP7', 'nama'=>'Penguatan Budaya Kepatuhan',              'satuan'=>'Skor',  'polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-MANRISK','perspektif'=>'Internal Process', 'kode'=>'MR-IP8', 'nama'=>'Penyelesaian Komitmen kepada Otoritas',   'satuan'=>'%',     'polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-MANRISK','perspektif'=>'Internal Process', 'kode'=>'MR-IP9', 'nama'=>'Penyelesaian Kasus Hukum',                'satuan'=>'%',     'polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-MANRISK','perspektif'=>'Internal Process', 'kode'=>'MR-IP10','nama'=>'Pengelolaan Legalitas Korporasi',         'satuan'=>'%',     'polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-MANRISK','perspektif'=>'Internal Process', 'kode'=>'MR-IP11','nama'=>'Inisiasi Project Internal Unit',          'satuan'=>'Jumlah','polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-MANRISK','perspektif'=>'Internal Process', 'kode'=>'MR-IP12','nama'=>'Penilaian Profil Risiko',                 'satuan'=>'Skor',  'polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-MANRISK','perspektif'=>'Internal Process', 'kode'=>'MR-IP13','nama'=>'Pelanggaran Risk Limit Konsolidasi',      'satuan'=>'Jumlah','polarity'=>'min','perubahan'=>'neg'],
            ['dir'=>'DIR-MANRISK','perspektif'=>'Internal Process', 'kode'=>'MR-IP14','nama'=>'Jumlah Fraud Internal',                   'satuan'=>'Jumlah','polarity'=>'min','perubahan'=>'neg'],
            ['dir'=>'DIR-MANRISK','perspektif'=>'Internal Process', 'kode'=>'MR-IP15','nama'=>'Penyusunan BPP/SOP Konsolidasi',          'satuan'=>'%',     'polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-MANRISK','perspektif'=>'Internal Process', 'kode'=>'MR-IP16','nama'=>'Penyelesaian BPP/SOP Konsolidasi',        'satuan'=>'%',     'polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-MANRISK','perspektif'=>'Learning & Growth','kode'=>'MR-LG1', 'nama'=>'Sosialisasi terkait Hukum',               'satuan'=>'Jumlah','polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-MANRISK','perspektif'=>'Learning & Growth','kode'=>'MR-LG2', 'nama'=>'Sosialisasi Anti Fraud',                  'satuan'=>'Jumlah','polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-MANRISK','perspektif'=>'Learning & Growth','kode'=>'MR-LG3', 'nama'=>'Internalisasi Budaya Risiko',             'satuan'=>'Skor',  'polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-MANRISK','perspektif'=>'Learning & Growth','kode'=>'MR-LG4', 'nama'=>'Kompetensi Pegawai',                      'satuan'=>'%',     'polarity'=>'max','perubahan'=>'pos'],
            ['dir'=>'DIR-MANRISK','perspektif'=>'Learning & Growth','kode'=>'MR-LG5', 'nama'=>'Denda/sanksi',                            'satuan'=>'Jumlah','polarity'=>'min','perubahan'=>'pos'],
        ];

        $rows = [];
        foreach ($data as $i => $d) {
            $rows[] = [
                'direktorat_id'      => $dirs[$d['dir']] ?? null,
                'perspektif'         => $d['perspektif'],
                'nama_kpi'           => $d['nama'],
                'kode'               => $d['kode'],
                'satuan'             => $d['satuan'],
                'bobot'              => 0,
                'polarity'           => $d['polarity'],
                'perubahan_polarity' => $d['perubahan'],
                'is_active'          => 1,
                'urutan'             => $i + 1,
                'created_at'         => $now,
                'updated_at'         => $now,
            ];
        }

        $this->db->table('kpi_unit')->insertBatch($rows);
        echo "KPI Unit seeded: " . count($rows) . " records.\n";
    }
}
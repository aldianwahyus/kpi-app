<?php
namespace App\Database\Seeds;
use CodeIgniter\Database\Seeder;

class KpiMasterSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['perspektif'=>'Financial',        'nama_kpi'=>'Laba Bersih',                         'kode'=>'F4.1',  'satuan'=>'Juta Rp','bobot'=>0.1000,'total_bobot_perspektif'=>0.25,  'polarity'=>'max','perubahan_polarity'=>'pos','is_kualitatif'=>0,'rubrik_sheet'=>null,     'urutan'=>1],
            ['perspektif'=>'Financial',        'nama_kpi'=>'Rasio Produktivitas Pegawai',         'kode'=>'F4.16', 'satuan'=>'Rp Juta','bobot'=>0.1000,'total_bobot_perspektif'=>null,  'polarity'=>'max','perubahan_polarity'=>'pos','is_kualitatif'=>0,'rubrik_sheet'=>null,     'urutan'=>2],
            ['perspektif'=>'Financial',        'nama_kpi'=>'Efisiensi Biaya Lembur Pegawai',      'kode'=>'F4.9',  'satuan'=>'%',      'bobot'=>0.0500,'total_bobot_perspektif'=>null,  'polarity'=>'min','perubahan_polarity'=>'neg','is_kualitatif'=>0,'rubrik_sheet'=>null,     'urutan'=>3],
            ['perspektif'=>'Financial',        'nama_kpi'=>'BOPO',                                'kode'=>'F4.6',  'satuan'=>'%',      'bobot'=>0.0500,'total_bobot_perspektif'=>null,  'polarity'=>'min','perubahan_polarity'=>'neg','is_kualitatif'=>0,'rubrik_sheet'=>null,     'urutan'=>4],
            ['perspektif'=>'Customer',         'nama_kpi'=>'Employee Satisfaction Index',         'kode'=>'C3.9',  'satuan'=>'Skor',   'bobot'=>0.1000,'total_bobot_perspektif'=>0.30,  'polarity'=>'max','perubahan_polarity'=>'pos','is_kualitatif'=>1,'rubrik_sheet'=>'esi',    'urutan'=>5],
            ['perspektif'=>'Customer',         'nama_kpi'=>'Kepuasan terhadap Program Pelatihan', 'kode'=>'C3.10', 'satuan'=>'Skor',   'bobot'=>0.1000,'total_bobot_perspektif'=>null,  'polarity'=>'max','perubahan_polarity'=>'pos','is_kualitatif'=>1,'rubrik_sheet'=>'pelatihan','urutan'=>6],
            ['perspektif'=>'Customer',         'nama_kpi'=>'Tingkat Retensi Pegawai',             'kode'=>'C3.27', 'satuan'=>'Skor',   'bobot'=>0.1000,'total_bobot_perspektif'=>null,  'polarity'=>'max','perubahan_polarity'=>'pos','is_kualitatif'=>0,'rubrik_sheet'=>null,     'urutan'=>7],
            ['perspektif'=>'Internal Process', 'nama_kpi'=>'Inisiasi Project Internal Unit',      'kode'=>'IP1.1', 'satuan'=>'Jumlah', 'bobot'=>0.1000,'total_bobot_perspektif'=>0.25,  'polarity'=>'max','perubahan_polarity'=>'pos','is_kualitatif'=>1,'rubrik_sheet'=>'milestone','urutan'=>8],
            ['perspektif'=>'Internal Process', 'nama_kpi'=>'Rasio Pemenuhan Pegawai (%)',         'kode'=>'IP2.28','satuan'=>'%',      'bobot'=>0.0500,'total_bobot_perspektif'=>null,  'polarity'=>'max','perubahan_polarity'=>'pos','is_kualitatif'=>0,'rubrik_sheet'=>null,     'urutan'=>9],
            ['perspektif'=>'Internal Process', 'nama_kpi'=>'Rasio Kedisiplinan Pegawai (%)',      'kode'=>'IP2.27','satuan'=>'%',      'bobot'=>0.0500,'total_bobot_perspektif'=>null,  'polarity'=>'max','perubahan_polarity'=>'neg','is_kualitatif'=>0,'rubrik_sheet'=>null,     'urutan'=>10],
            ['perspektif'=>'Internal Process', 'nama_kpi'=>'Penyelesaian Temuan Audit',           'kode'=>'IP2.23','satuan'=>'SLA',    'bobot'=>0.0500,'total_bobot_perspektif'=>null,  'polarity'=>'max','perubahan_polarity'=>'neg','is_kualitatif'=>0,'rubrik_sheet'=>null,     'urutan'=>11],
            ['perspektif'=>'Learning & Growth','nama_kpi'=>'Kompetensi Pegawai',                  'kode'=>'LG1.1', 'satuan'=>'%',      'bobot'=>0.0500,'total_bobot_perspektif'=>0.20,  'polarity'=>'max','perubahan_polarity'=>'pos','is_kualitatif'=>1,'rubrik_sheet'=>'kompetensi','urutan'=>12],
            ['perspektif'=>'Learning & Growth','nama_kpi'=>'Pencapaian Biaya Training Pegawai',   'kode'=>'LG1.3', 'satuan'=>'%',      'bobot'=>0.0250,'total_bobot_perspektif'=>null,  'polarity'=>'max','perubahan_polarity'=>'pos','is_kualitatif'=>0,'rubrik_sheet'=>null,     'urutan'=>13],
            ['perspektif'=>'Learning & Growth','nama_kpi'=>'Rata-rata Skor Kompetensi Pegawai',   'kode'=>'LG1.4', 'satuan'=>'%',      'bobot'=>0.0500,'total_bobot_perspektif'=>null,  'polarity'=>'max','perubahan_polarity'=>'pos','is_kualitatif'=>1,'rubrik_sheet'=>'kompetensi','urutan'=>14],
            ['perspektif'=>'Learning & Growth','nama_kpi'=>'Jumlah Sertifikasi Pegawai',          'kode'=>'LG1.2', 'satuan'=>'Jumlah', 'bobot'=>0.0500,'total_bobot_perspektif'=>null,  'polarity'=>'max','perubahan_polarity'=>'pos','is_kualitatif'=>0,'rubrik_sheet'=>null,     'urutan'=>15],
            ['perspektif'=>'Learning & Growth','nama_kpi'=>'Denda/sanksi',                        'kode'=>'LG2.1', 'satuan'=>'Jumlah', 'bobot'=>0.0250,'total_bobot_perspektif'=>null,  'polarity'=>'min','perubahan_polarity'=>'pos','is_kualitatif'=>0,'rubrik_sheet'=>null,     'urutan'=>16],
        ];

        $now = date('Y-m-d H:i:s');
        foreach ($data as &$row) {
            $row['created_at'] = $now;
            $row['updated_at'] = $now;
            $row['is_active']  = 1;
        }

        $this->db->table('kpi_master')->insertBatch($data);
        echo "KPI Master seeded: " . count($data) . " records.\n";
    }
}
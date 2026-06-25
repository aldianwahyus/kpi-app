<?php
namespace App\Database\Seeds;
use CodeIgniter\Database\Seeder;

class MenuListSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Kosongkan tabel agar data lama terhapus (mencegah error duplikasi)
        $this->db->table('role_permission')->truncate();
        $this->db->table('menu_list')->truncate();

        $data = [
            // Penilaian
            ['kode_menu'=>'penilaian',       'nama_menu'=>'Input Penilaian',       'grup'=>'Penilaian', 'urutan'=>1],
            ['kode_menu'=>'penilaian_unit',  'nama_menu'=>'KPI Unit',              'grup'=>'Penilaian', 'urutan'=>2],
            ['kode_menu'=>'rekap',           'nama_menu'=>'Rekap & Ranking',       'grup'=>'Penilaian', 'urutan'=>3],
            ['kode_menu'=>'approval',        'nama_menu'=>'Approval Penilaian',    'grup'=>'Penilaian', 'urutan'=>4],

            // Master Data
            ['kode_menu'=>'master_direktorat','nama_menu'=>'Direktorat & KPI Unit', 'grup'=>'Master Data', 'urutan'=>1],
            ['kode_menu'=>'master_unitkerja', 'nama_menu'=>'Data Unit Kerja',       'grup'=>'Master Data', 'urutan'=>2],
            ['kode_menu'=>'master_kpidivisi', 'nama_menu'=>'KPI per Divisi',       'grup'=>'Master Data', 'urutan'=>3],
            ['kode_menu'=>'kpi_pegawai',      'nama_menu'=>'KPI Per Pegawai',     'grup'=>'Master Data', 'urutan'=>4],
            ['kode_menu'=>'pegawai',          'nama_menu'=>'Data Pegawai',        'grup'=>'Master Data', 'urutan'=>5],
            ['kode_menu'=>'master_periode',   'nama_menu'=>'Periode',             'grup'=>'Master Data', 'urutan'=>6],
            ['kode_menu'=>'master_users',     'nama_menu'=>'Manajemen User',      'grup'=>'Master Data', 'urutan'=>7],

            // Laporan
            ['kode_menu'=>'laporan_pdf',   'nama_menu'=>'Export PDF',    'grup'=>'Laporan', 'urutan'=>1],
            ['kode_menu'=>'laporan_excel', 'nama_menu'=>'Export Excel',  'grup'=>'Laporan', 'urutan'=>2],

            // Tools
            ['kode_menu'=>'notifikasi', 'nama_menu'=>'Notifikasi Email', 'grup'=>'Tools', 'urutan'=>1],
            ['kode_menu'=>'ai',         'nama_menu'=>'AI Asisten KPI',   'grup'=>'Tools', 'urutan'=>2],
        ];

        foreach ($data as &$row) { $row['is_active'] = 1; }
        $this->db->table('menu_list')->insertBatch($data);

        // Default permission per role
        $menus = $this->db->table('menu_list')->get()->getResultArray();
        $defaultPermission = [
            'admin'    => 'all',
            'hr'       => ['penilaian','penilaian_unit','rekap','approval','master_periode','master_kpidivisi','pegawai','laporan_pdf','laporan_excel','notifikasi','ai'],
            'drafter'  => ['penilaian'], 
            'approver' => ['penilaian', 'approval', 'rekap'], 
            'pegawai'  => ['rubrik'], 
        ];

        $rows = [];
        foreach ($defaultPermission as $role => $allowedMenus) {
            foreach ($menus as $menu) {
                $allowed = $allowedMenus === 'all' || in_array($menu['kode_menu'], $allowedMenus);
                $rows[] = [
                    'role'     => $role,
                    'menu_id'  => $menu['id'],
                    'can_view' => $allowed ? 1 : 0,
                    'can_edit' => $allowed ? 1 : 0,
                ];
            }
        }
        $this->db->table('role_permission')->insertBatch($rows);

        echo "Menu & Permission seeded.\n";
    }
}
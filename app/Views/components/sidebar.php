<?php 
$role = session()->get('role'); 

$roleLabels = [
    'admin'    => 'Admin',
    'hr'       => 'HR Manager',
    'drafter'  => 'Drafter',
    'approver' => 'Approver',
    'pegawai'  => 'Pegawai',
];

// --- INISIALISASI PERMISSION MODEL ---
$permModel = new \App\Models\RolePermissionModel();

// Menggunakan Anonymous Function agar aman dan tidak error saat view diload ulang
$canShow = function($kode) use ($permModel, $role) {
    if ($role === 'admin') return true; // Admin bisa melihat semuanya
    
    // Pastikan model Anda memiliki method canAccess($role, $kode)
    return $permModel->canAccess($role, $kode);
};
?>

<style>
  /* Tema Hijau Syariah untuk Sidebar */
  #sidebar {
    /* Menggunakan gradasi hijau zamrud ke hijau tua */
    background: linear-gradient(180deg, #04704b 0%, #024a31 100%) !important;
    color: #ffffff !important;
  }
  
  #sidebar a {
    color: rgba(255, 255, 255, 0.8) !important;
    transition: all 0.2s ease-in-out;
    padding: 8px 12px;
    border-radius: 6px;
  }
  
  #sidebar a:hover {
    color: #ffffff !important;
    background: rgba(255, 255, 255, 0.15) !important; /* Efek hover menyala sedikit */
  }

  #sidebar .nav-section a:hover {
    background: transparent !important; /* Header menu tidak perlu background saat di-hover */
  }
</style>

<nav id="sidebar">
  <div class="brand">
    <div style="text-align:center;padding:10px 16px 8px">
      <div style="background:#ffffff;border-radius:8px; padding:8px 12px;display:inline-block; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <img src="<?= base_url('assets/images/logo.png') ?>" alt="Bank NTB Syariah" style="max-width:130px;height:auto;display:block">
      </div>
    </div>
    <div style="text-align:center;padding-bottom:10px">
      <div style="color:rgba(255,255,255,.7);font-size:10px; letter-spacing:.06em;text-transform:uppercase; font-weight: 500;">
        Sistem Penilaian Kinerja
      </div>
    </div>
  </div>

  <ul class="nav flex-column mt-1" style="list-style:none;padding:0 10px;">
    
    <!-- DASHBOARD (Biasanya diakses semua role) -->
    <li class="nav-section">
      <a href="#collapseMenuUtama" data-bs-toggle="collapse" role="button" aria-expanded="true" style="color:inherit; text-decoration:none; display:flex; justify-content:between; width:100%; font-weight:600;">
        <span>Menu Utama</span>
        <i class="ti ti-chevron-down ms-auto arrow-icon"></i>
      </a>
    </li>
    <div class="collapse show" id="collapseMenuUtama">
      <li class="nav-item">
        <a href="<?= base_url('dashboard') ?>" style="display:flex; align-items:center; gap:8px; text-decoration:none;">
          <i class="ti ti-layout-dashboard"></i> Dashboard
        </a>
      </li>
    </div>

    <!-- PENILAIAN -->
      <?php 
      // Header section muncul jika user punya salah satu dari akses di bawah ini
      if ($canShow('penilaian') || $canShow('rekap')): 
      ?>
      <li class="nav-section mt-3">
        <a href="#collapsePenilaian" data-bs-toggle="collapse" role="button" aria-expanded="false" style="color:inherit; text-decoration:none; display:flex; justify-content:between; width:100%; font-weight:600;">
          <span>Penilaian</span>
          <i class="ti ti-chevron-down ms-auto arrow-icon"></i>
        </a>
      </li>
      <div class="collapse" id="collapsePenilaian">
        
        <?php if ($canShow('penilaian')): ?>
        <li class="nav-item">
          <a href="<?= base_url('penilaian') ?>" style="display:flex; align-items:center; gap:8px; text-decoration:none;">
            <i class="ti ti-clipboard-list"></i> Input Penilaian
            <?php
            // Badge jumlah penilaian yang sudah disubmit Drafter tapi
            // belum di-approve/ditolak — hanya relevan untuk Approver,
            // dibatasi ke divisinya sendiri (sama seperti scope Approver
            // di modul Penilaian/Rekap lainnya).
            if ($role === 'approver'):
                $periodeAktifSidebar = (new \App\Models\PeriodeModel())->getAktif();
                $submittedCount = 0;
                if ($periodeAktifSidebar) {
                    $myPegawaiIdSidebar = session()->get('pegawai_id');
                    $myDivisiIdSidebar  = $myPegawaiIdSidebar
                        ? ((new \App\Models\PegawaiModel())->find($myPegawaiIdSidebar)['divisi_id'] ?? null)
                        : null;
                    $submittedCount = (new \App\Models\PenilaianModel())
                        ->getCountSubmittedUntukDivisi($periodeAktifSidebar['id'], $myDivisiIdSidebar);
                }
                if ($submittedCount > 0):
            ?>
            <span id="badge-submitted-count" class="badge bg-warning text-dark ms-1" style="font-size:10px">
              <?= $submittedCount ?>
            </span>
            <?php endif; endif; ?>
          </a>
        </li>
        <?php endif; ?>

        <?php if ($canShow('rekap')): ?>
        <li class="nav-item">
          <a href="<?= base_url('rekap') ?>" style="display:flex; align-items:center; gap:8px; text-decoration:none;">
            <i class="ti ti-table"></i> Rekap & Ranking
          </a>
        </li>
        <?php endif; ?>
        <?php if ($role === 'admin'): ?>
        <li class="nav-item">
          <a href="<?= base_url('draft-ulang') ?>">
            <i class="ti ti-refresh-dot"></i> Draft Ulang
            <?php
            $pendingCount = (new \App\Models\DraftUlangRequestModel())->getCountPending();
            if ($pendingCount > 0):
            ?>
            <span class="badge bg-warning text-dark ms-1" style="font-size:10px">
              <?= $pendingCount ?>
            </span>
            <?php endif; ?>
          </a>
        </li>
        <?php endif; ?>
      </div>
      <?php endif; ?>

    <!-- RUBRIK SAYA -->
    <!-- DINONAKTIFKAN SEMENTARA: rute rubrik/esi, rubrik/pelatihan, dan nilai-saya
         belum memiliki Controller/Route yang dibuat, sehingga sebelumnya menyebabkan
         404 bagi pengguna role 'pegawai'. Aktifkan kembali blok ini setelah modul
         Rubrik Saya benar-benar dibangun. -->
    <?php if (false && $canShow('rubrik')): ?>
    <li class="nav-section mt-3">
      <a href="#collapseRubrik" data-bs-toggle="collapse" role="button" aria-expanded="false" style="color:inherit; text-decoration:none; display:flex; justify-content:between; width:100%; font-weight:600;">
        <span>Rubrik Saya</span>
        <i class="ti ti-chevron-down ms-auto arrow-icon"></i>
      </a>
    </li>
    <div class="collapse" id="collapseRubrik">
      <li class="nav-item"><a href="<?= base_url('rubrik/esi') ?>" style="display:flex; align-items:center; gap:8px; text-decoration:none;"><i class="ti ti-mood-happy"></i> Kuesioner ESI</a></li>
      <li class="nav-item"><a href="<?= base_url('rubrik/pelatihan') ?>" style="display:flex; align-items:center; gap:8px; text-decoration:none;"><i class="ti ti-school"></i> Evaluasi Pelatihan</a></li>
      <li class="nav-item"><a href="<?= base_url('nilai-saya') ?>" style="display:flex; align-items:center; gap:8px; text-decoration:none;"><i class="ti ti-award"></i> Nilai KPI Saya</a></li>
    </div>
    <?php endif; ?>

    <!-- MASTER DATA -->
    <?php
    // Header section muncul jika role punya akses ke SALAH SATU menu di
    // dalamnya (sama seperti pola section "Penilaian" di atas) — bukan
    // lagi bergantung pada kode 'master_data' terpisah, yang sebelumnya
    // membuat item yang sudah diberi izin sendiri (mis. 'kpi_pegawai')
    // tetap tersembunyi selama toggle grup itu sendiri belum dicentang.
    $showMasterData = $canShow('master_direktorat') || $canShow('master_unitkerja')
        || $canShow('master_kpidivisi') || $canShow('kpi_pegawai') || $canShow('master_target')
        || $canShow('pegawai') || $canShow('master_periode') || $canShow('master_users');
    ?>
    <?php if ($showMasterData || $role === 'admin'): ?>
    <li class="nav-section mt-3">
      <a href="#collapseMaster" data-bs-toggle="collapse" role="button" aria-expanded="false" style="color:inherit; text-decoration:none; display:flex; justify-content:between; width:100%; font-weight:600;">
        <span>Master Data</span>
        <i class="ti ti-chevron-down ms-auto arrow-icon"></i>
      </a>
    </li>
    <div class="collapse" id="collapseMaster">
      <?php if ($canShow('master_direktorat')): ?>
      <li class="nav-item"><a href="<?= base_url('master/direktorat') ?>" style="display:flex; align-items:center; gap:8px; text-decoration:none;"><i class="ti ti-building"></i> Direktorat</a></li>
      <?php endif; ?>
      <?php if ($canShow('master_unitkerja')): ?>
      <li class="nav-item"><a href="<?= base_url('master/unit-kerja') ?>" style="display:flex; align-items:center; gap:8px; text-decoration:none;"><i class="ti ti-building-community"></i> Unit Kerja</a></li>
      <?php endif; ?>
      <?php if ($canShow('master_kpidivisi')): ?>
      <li class="nav-item"><a href="<?= base_url('master/kpi-divisi') ?>" style="display:flex; align-items:center; gap:8px; text-decoration:none;"><i class="ti ti-sitemap"></i> KPI per Unit Kerja</a></li>
      <?php endif; ?>
      <?php if ($canShow('kpi_pegawai')): ?>
      <li class="nav-item"><a href="<?= base_url('kpi-pegawai') ?>" style="display:flex; align-items:center; gap:8px; text-decoration:none;"><i class="ti ti-user-check"></i> KPI Per Pegawai</a></li>
      <?php endif; ?>
      <?php if ($canShow('master_target')): ?>
      <li class="nav-item"><a href="<?= base_url('master-target') ?>" style="display:flex; align-items:center; gap:8px; text-decoration:none;"><i class="ti ti-target-arrow"></i> Master Target</a></li>
      <?php endif; ?>
      <?php if ($canShow('pegawai')): ?>
      <li class="nav-item"><a href="<?= base_url('pegawai') ?>" style="display:flex; align-items:center; gap:8px; text-decoration:none;"><i class="ti ti-users"></i> Data Pegawai</a></li>
      <?php endif; ?>
      <?php if ($canShow('master_periode')): ?>
      <li class="nav-item"><a href="<?= base_url('master/periode') ?>" style="display:flex; align-items:center; gap:8px; text-decoration:none;"><i class="ti ti-calendar"></i> Periode</a></li>
      <?php endif; ?>
      <?php if ($canShow('master_users')): ?>
      <li class="nav-item"><a href="<?= base_url('master/users') ?>" style="display:flex; align-items:center; gap:8px; text-decoration:none;"><i class="ti ti-settings"></i> Manajemen User</a></li>
      <?php endif; ?>
      <?php if ($role === 'admin'): ?>
      <li class="nav-item">
        <a href="<?= base_url('master/role-permission') ?>">
          <i class="ti ti-shield-lock"></i> Hak Akses Role
        </a>
      </li>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- TOOLS -->
    <?php if ($canShow('notifikasi') || $canShow('ai')): ?>
    <li class="nav-section mt-3">
      <a href="#collapseTools" data-bs-toggle="collapse" role="button" aria-expanded="false" style="color:inherit; text-decoration:none; display:flex; justify-content:between; width:100%; font-weight:600;">
        <span>Tools</span>
        <i class="ti ti-chevron-down ms-auto arrow-icon"></i>
      </a>
    </li>
    <div class="collapse" id="collapseTools">
      <?php if ($canShow('notifikasi')): ?>
      <li class="nav-item"><a href="<?= base_url('notifikasi') ?>" style="display:flex; align-items:center; gap:8px; text-decoration:none;"><i class="ti ti-bell"></i> Notifikasi Email</a></li>
      <?php endif; ?>
      <?php if ($canShow('ai')): ?>
      <li class="nav-item"><a href="<?= base_url('ai') ?>" style="display:flex; align-items:center; gap:8px; text-decoration:none;"><i class="ti ti-robot"></i> AI Asisten KPI</a></li>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- LAPORAN & AKUN -->
    <!-- Section ini SELALU ditampilkan (tidak digate permission) karena
         berisi "Profil" — akses ganti password sendiri yang wajib bisa
         dijangkau setiap user yang login, apa pun izin Laporan-nya.
         Item Export PDF/Excel di dalamnya tetap digate individual. -->
    <li class="nav-section mt-3">
      <a href="#collapseLaporan" data-bs-toggle="collapse" role="button" aria-expanded="false" style="color:inherit; text-decoration:none; display:flex; justify-content:between; width:100%; font-weight:600;">
        <span>Laporan & Akun</span>
        <i class="ti ti-chevron-down ms-auto arrow-icon"></i>
      </a>
    </li>
    <div class="collapse" id="collapseLaporan">
      <?php if ($canShow('laporan_pdf')): ?>
      <li class="nav-item"><a href="<?= base_url('laporan/pdf') ?>" style="display:flex; align-items:center; gap:8px; text-decoration:none;"><i class="ti ti-file-text"></i> Export PDF Rekap</a></li>
      <?php endif; ?>
      <?php if ($canShow('laporan_excel')): ?>
      <li class="nav-item"><a href="<?= base_url('laporan/excel') ?>" style="display:flex; align-items:center; gap:8px; text-decoration:none;"><i class="ti ti-file-spreadsheet"></i> Export Excel Rekap</a></li>
      <?php endif; ?>
      <?php if ($role === 'admin'): ?>
      <li class="nav-item"><a href="<?= base_url('arsip-periode') ?>" style="display:flex; align-items:center; gap:8px; text-decoration:none;"><i class="ti ti-archive"></i> Arsip Periode</a></li>
      <?php endif; ?>
      <li class="nav-item"><a href="<?= base_url('profil') ?>" style="display:flex; align-items:center; gap:8px; text-decoration:none;"><i class="ti ti-user-circle"></i> Profil</a></li>
    </div>

  </ul>

  <!-- Bagian Bawah / Footer Sidebar -->
  <div style="bottom:0; width:100%; padding:12px 20px; border-top:1px solid rgba(255,255,255,.1); background:rgba(0,0,0,.25);">
    <div style="font-size:13px; color:#ffffff; font-weight:600;"><?= esc(session()->get('nama')) ?></div>
    <div style="font-size:11px; color:#F2C94C; margin-top:2px; font-weight: 500;"><?= esc($role_labels[$role] ?? ucfirst($role)) ?></div>
    
    <a href="<?= base_url('auth/logout') ?>" style="display:inline-flex; align-items:center; gap:5px; margin-top:10px; font-size:12px; color:rgba(255,255,255,.7); text-decoration:none; transition: color 0.2s;" onmouseover="this.style.color='#ffffff'" onmouseout="this.style.color='rgba(255,255,255,.7)'">
      <i class="ti ti-logout"></i> Logout
    </a>
  </div>
</nav>
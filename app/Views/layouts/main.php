<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?= csrf_token() ?>">
  <meta name="csrf-hash"  content="<?= csrf_hash() ?>">

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Tabler Icons -->
  <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.44.0/tabler-icons.min.css" rel="stylesheet">
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <!-- Chart.js -->

  <link rel="icon" type="image/png"
        href="<?= base_url('assets/images/logo.png') ?>">
  <title><?= esc($title ?? 'Aplikasi KPI') ?> — Bank NTB Syariah</title>

  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

  <style>
    :root {
      --sidebar-width: 240px;
      --header-height: 56px;
      --biru-tua: #1F4E79;
      --biru-mid: #2E75B6;
      --biru-muda: #BDD7EE;
      --hijau-tua: #375623;
    }

    body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; }

    /* ── Sidebar ── */
    #sidebar {
      position: fixed; top: 0; left: 0; bottom: 0;
      width: var(--sidebar-width);
      background: var(--biru-tua);
      z-index: 1000; overflow-y: auto;
      transition: transform .25s ease;
    }
    #sidebar .brand {
      padding: 16px 20px 12px;
      border-bottom: 1px solid rgba(255,255,255,.1);
    }
    #sidebar .brand-title {
      color: #fff; font-size: 15px; font-weight: 600; line-height: 1.3;
    }
    #sidebar .brand-sub {
      color: rgba(255,255,255,.5); font-size: 11px;
    }
    #sidebar .nav-section {
      padding: 12px 12px 4px;
      font-size: 10px; font-weight: 600; letter-spacing: .08em;
      color: rgba(255,255,255,.35); text-transform: uppercase;
    }
    #sidebar .nav-item a {
      display: flex; align-items: center; gap: 10px;
      padding: 9px 20px; color: rgba(255,255,255,.75);
      text-decoration: none; font-size: 13px;
      border-left: 3px solid transparent;
      transition: all .15s;
    }
    #sidebar .nav-item a:hover,
    #sidebar .nav-item a.active {
      color: #fff; background: rgba(255,255,255,.08);
      border-left-color: #70AD47;
    }
    #sidebar .nav-item a i { font-size: 17px; opacity: .8; }
    #sidebar .nav-item a.active i { opacity: 1; }

    /* ── Header ── */
    #header {
      position: fixed; top: 0;
      left: var(--sidebar-width); right: 0;
      height: var(--header-height);
      background: #fff;
      border-bottom: 1px solid #e5e7eb;
      display: flex; align-items: center;
      padding: 0 20px; z-index: 999;
      gap: 12px;
    }
    #header .page-title {
      font-size: 15px; font-weight: 600;
      color: var(--biru-tua); flex: 1;
    }
    #header .user-badge {
      display: flex; align-items: center; gap: 8px;
      font-size: 13px; color: #374151;
    }
    #header .user-avatar {
      width: 32px; height: 32px; border-radius: 50%;
      background: var(--biru-mid);
      display: flex; align-items: center; justify-content: center;
      color: #fff; font-size: 13px; font-weight: 600;
    }
    .role-badge {
      font-size: 10px; font-weight: 600; padding: 2px 8px;
      border-radius: 99px; text-transform: uppercase;
    }
    .role-admin    { background: #DBEAFE; color: #1D4ED8; }
    .role-hr       { background: #D1FAE5; color: #065F46; }
    .role-drafter  { background: #FEF3C7; color: #92400E; }
    .role-approver { background: #E0F2FE; color: #0369A1; }
    .role-pegawai  { background: #F3E8FF; color: #6B21A8; }

    /* ── Main content ── */
    #main-content {
      margin-left: var(--sidebar-width);
      margin-top: var(--header-height);
      padding: 24px; min-height: calc(100vh - var(--header-height));
    }

    /* ── Cards ── */
    .stat-card {
      background: #fff; border-radius: 10px;
      padding: 20px; border: 1px solid #e5e7eb;
      transition: box-shadow .2s;
    }
    .stat-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.08); }
    .stat-icon {
      width: 44px; height: 44px; border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      font-size: 22px;
    }
    .stat-value { font-size: 26px; font-weight: 700; color: #111827; }
    .stat-label { font-size: 12px; color: #6B7280; margin-top: 2px; }

    /* ── Responsive ── */
    @media (max-width: 768px) {
      #sidebar { transform: translateX(-100%); }
      #sidebar.show { transform: translateX(0); }
      #header { left: 0; }
      #main-content { margin-left: 0; }
    }
  </style>
  <?= $extra_css ?? '' ?>
</head>
<body>

<!-- ── SIDEBAR ── -->
<?= view('components/sidebar') ?>

<!-- ── HEADER ── -->
<?= view('components/header', ['title' => $title ?? 'Dashboard']) ?>

<!-- ── CONTENT ── -->
<div id="main-content">
  <?= $content ?? '' ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Sidebar toggle mobile
  document.getElementById('sidebarToggle')?.addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('show');
  });

  // Active nav link
  const currentPath = window.location.pathname;
  document.querySelectorAll('#sidebar .nav-item a').forEach(link => {
    if (currentPath.startsWith(link.getAttribute('href'))) {
      link.classList.add('active');
    }
  });

  // ── SweetAlert2: Toast notifikasi dari flash message session ──
  const KpiToast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3500,
    timerProgressBar: true,
    didOpen: (toast) => {
      toast.addEventListener('mouseenter', Swal.stopTimer);
      toast.addEventListener('mouseleave', Swal.resumeTimer);
    }
  });

  <?php if (session()->getFlashdata('success')): ?>
    KpiToast.fire({
      icon: 'success',
      title: <?= json_encode(session()->getFlashdata('success')) ?>,
    });
  <?php endif; ?>

  <?php if (session()->getFlashdata('error')): ?>
    KpiToast.fire({
      icon: 'error',
      title: <?= json_encode(session()->getFlashdata('error')) ?>,
    });
  <?php endif; ?>

  // ── SweetAlert2: Pengganti confirm() bawaan browser ──
  // Dipasang sebagai window.confirmAction agar dapat dipanggil dari onclick/onsubmit
  // pada elemen mana pun tanpa perlu mengubah struktur form/link yang sudah ada.
  //
  // Cara pakai pada link biasa (menggantikan onclick="return confirm('...')"):
  //   onclick="return confirmAction(event, { text: 'Hapus data ini?' })"
  //
  // Cara pakai pada form submit (menggantikan onsubmit="return confirm('...')"):
  //   onsubmit="return confirmAction(event, { text: '...', confirmText: 'Ya, Approve' })"
  window.confirmAction = function (event, options) {
    event.preventDefault();

    const target = event.currentTarget;
    const opts = Object.assign({
      title: 'Konfirmasi',
      text: 'Apakah Anda yakin?',
      icon: 'warning',
      confirmText: 'Ya, Lanjutkan',
      cancelText: 'Batal',
      danger: false,
    }, options || {});

    Swal.fire({
      title: opts.title,
      text: opts.text,
      icon: opts.icon,
      showCancelButton: true,
      confirmButtonText: opts.confirmText,
      cancelButtonText: opts.cancelText,
      confirmButtonColor: opts.danger ? '#C00000' : '#2E75B6',
      cancelButtonColor: '#6c757d',
      reverseButtons: true,
    }).then((result) => {
      if (!result.isConfirmed) return;

      if (target.tagName === 'FORM') {
        target.submit();
      } else if (target.tagName === 'A' && target.href) {
        window.location.href = target.href;
      } else if (target.tagName === 'BUTTON' && target.form) {
        target.form.submit();
      }
    });

    return false;
  };
</script>
<?= $extra_js ?? '' ?>
</body>
</html>
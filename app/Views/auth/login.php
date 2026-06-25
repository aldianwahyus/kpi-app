<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login — Aplikasi KPI</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      /* background: linear-gradient(135deg, #1F4E79 0%, #2E75B6 50%, #0F6E56 100%); */
      background: linear-gradient(135deg, #0F6E56 0%, #16A085 50%, #27AE60 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
    }
    .login-card {
      width: 100%;
      max-width: 420px;
    }
    .card {
      border-radius: 16px;
      box-shadow: 0 20px 60px rgba(0,0,0,0.2);
    }
    .logo-wrap {
      text-align: center;
      padding: 28px 0 16px;
    }
    .logo-wrap img {
      max-width: 180px;
      height: auto;
    }
    .app-subtitle {
      text-align: center;
      font-size: 13px;
      color: #6B7280;
      margin-top: 4px;
      margin-bottom: 20px;
      font-weight: 500;
      letter-spacing: .02em;
    }
    .divider {
      border: none;
      border-top: 1px solid #f0f0f0;
      margin: 0 0 20px;
    }
    .form-label { font-size: 13px; font-weight: 600; color: #374151; }
    .form-control {
      border-radius: 8px;
      font-size: 13px;
      padding: 10px 14px;
    }
    .form-control:focus {
      border-color: #2E75B6;
      box-shadow: 0 0 0 3px rgba(46,117,182,0.15);
    }
    .btn-login {
      background: linear-gradient(135deg, #1F4E79, #2E75B6);
      border: none;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 600;
      padding: 11px;
      letter-spacing: .02em;
    }
    .btn-login:hover {
      background: linear-gradient(135deg, #163a5f, #1F4E79);
    }
    .footer-text {
      text-align: center;
      font-size: 11px;
      color: rgba(255,255,255,0.6);
      margin-top: 16px;
    }
  </style>
</head>
<body>

<div class="login-card">
  <div class="card border-0">

    <!-- Logo -->
    <div class="logo-wrap">
      <img src="<?= base_url('assets/images/logo.png') ?>"
           alt="Bank NTB Syariah">
      <div class="app-subtitle">Sistem Penilaian Kinerja Pegawai</div>
    </div>

    <hr class="divider">

    <div class="card-body px-4 pb-4 pt-0">

      <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible py-2 mb-3"
             style="font-size:13px;border-radius:8px">
          <i class="ti ti-alert-circle me-1"></i>
          <?= session()->getFlashdata('error') ?>
          <button type="button" class="btn-close"
                  data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success py-2 mb-3"
             style="font-size:13px;border-radius:8px">
          <?= session()->getFlashdata('success') ?>
        </div>
      <?php endif; ?>

      <form action="<?= base_url('auth/login') ?>" method="post">
        <?= csrf_field() ?>

        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" name="email"
                 class="form-control"
                 value="<?= old('email') ?>"
                 placeholder="nama@bankntbsyariah.co.id"
                 required autofocus>
        </div>

        <div class="mb-4">
          <label class="form-label">Password</label>
          <input type="password" name="password"
                 class="form-control"
                 placeholder="••••••••"
                 required>
        </div>

        <button type="submit"
                class="btn btn-login btn-primary w-100 text-white">
          Masuk
        </button>
      </form>

    </div>
  </div>

  <div class="footer-text">
    © <?= date('Y') ?> Divisi Human Capital Development PT Bank NTB Syariah — Hak Cipta Dilindungi
  </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.44.0/tabler-icons.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
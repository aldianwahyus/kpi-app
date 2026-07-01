<div class="mb-3">
  <h5 class="fw-semibold" style="color:#1F4E79">
    <i class="ti ti-user-circle me-1"></i> Profil Saya
  </h5>
</div>

<div class="card border-0 shadow-sm" style="max-width:500px">
  <div class="card-body">

    <!-- Info role -->
    <div class="d-flex align-items-center gap-3 mb-4 p-3 rounded"
         style="background:#E6F1FB">
      <div class="user-avatar" style="width:50px;height:50px;font-size:20px">
        <?= strtoupper(substr($user['nama'],0,1)) ?>
      </div>
      <div>
        <div class="fw-semibold" style="color:#1F4E79;font-size:15px">
          <?= esc($user['nama']) ?>
        </div>
        <div style="font-size:12px;color:#888">
          <?= esc($user['email']) ?>
          &nbsp;·&nbsp;
          <span class="role-badge role-<?= esc($user['role']) ?>">
            <?= esc(ucfirst($user['role'])) ?>
          </span>
        </div>
        <div style="font-size:11px;color:#aaa;margin-top:3px">
          Last login:
          <?= $user['last_login']
              ? date('d M Y H:i', strtotime($user['last_login']))
              : 'Belum pernah' ?>
        </div>
      </div>
    </div>

    <?php if (session()->getFlashdata('errors')): ?>
      <div class="alert alert-danger py-2" style="font-size:13px">
        <ul class="mb-0">
          <?php foreach (session()->getFlashdata('errors') as $e): ?>
            <li><?= esc($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form action="<?= $action ?>" method="post">
      <?= csrf_field() ?>
      <div class="row g-3">
        <div class="col-12">
          <label class="form-label fw-semibold small">Nama Lengkap</label>
          <input type="text" name="nama"
                 class="form-control form-control-sm"
                 value="<?= old('nama', $user['nama']) ?>"
                 required>
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold small">Email</label>
          <input type="email" name="email"
                 class="form-control form-control-sm"
                 value="<?= old('email', $user['email']) ?>"
                 required>
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold small">
            Password Baru
            <small class="text-muted">(kosongkan jika tidak diubah)</small>
          </label>
          <input type="password" name="password"
                 class="form-control form-control-sm"
                 placeholder="Minimal 6 karakter">
        </div>
      </div>
      <hr class="my-3">
      <button type="submit" class="btn btn-primary btn-sm px-4">
        <i class="ti ti-device-floppy me-1"></i> Simpan Perubahan
      </button>
    </form>
  </div>
</div>
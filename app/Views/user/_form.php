<div class="d-flex align-items-center gap-2 mb-3">
  <a href="<?= base_url('master/users') ?>"
     class="btn btn-sm btn-light border">
    <i class="ti ti-arrow-left"></i>
  </a>
  <h5 class="mb-0 fw-semibold" style="color:#1F4E79">
    <?= $user ? 'Edit User' : 'Tambah User Baru' ?>
  </h5>
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

<div class="card border-0 shadow-sm" style="max-width:550px">
  <div class="card-body">
    <form action="<?= $action ?>" method="post">
      <?= csrf_field() ?>
      <div class="row g-3">

        <div class="col-12">
          <label class="form-label fw-semibold small">
            Nama Lengkap <span class="text-danger">*</span>
          </label>
          <input type="text" name="nama"
                 class="form-control form-control-sm"
                 value="<?= old('nama', $user['nama'] ?? '') ?>"
                 required>
        </div>

        <div class="col-12">
          <label class="form-label fw-semibold small">
            Email <span class="text-danger">*</span>
          </label>
          <input type="email" name="email"
                 class="form-control form-control-sm"
                 value="<?= old('email', $user['email'] ?? '') ?>"
                 required>
        </div>

        <div class="col-12">
          <label class="form-label fw-semibold small">
            Password
            <?= $user
                ? '<small class="text-muted">(kosongkan jika tidak diubah)</small>'
                : '<span class="text-danger">*</span>' ?>
          </label>
          <input type="password" name="password"
                 class="form-control form-control-sm"
                 placeholder="<?= $user
                     ? 'Isi untuk mengubah password'
                     : 'Minimal 6 karakter' ?>"
                 <?= !$user ? 'required' : '' ?>>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold small">
            Role <span class="text-danger">*</span>
          </label>
          <select name="role" class="form-select form-select-sm" required>
            <?php foreach ([
                'pegawai' => 'Pegawai',
                'manajer' => 'Manajer',
                'hr'      => 'HR Manager',
                'admin'   => 'Admin',
            ] as $val => $lbl): ?>
              <option value="<?= $val ?>"
                <?= old('role', $user['role'] ?? 'pegawai') === $val
                    ? 'selected' : '' ?>>
                <?= $lbl ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold small">
            Hubungkan ke Pegawai
          </label>
          <select name="pegawai_id" class="form-select form-select-sm">
            <?php foreach ($pegawai_dd as $id => $nama): ?>
              <option value="<?= $id ?>"
                <?= old('pegawai_id', $user['pegawai_id'] ?? '') == $id
                    ? 'selected' : '' ?>>
                <?= esc($nama) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-12">
          <div class="p-3 rounded"
               style="background:#f8fafc;
                      border:1px solid #e5e7eb;font-size:12px">
            <i class="ti ti-info-circle me-1"
               style="color:#2E75B6"></i>
            <strong>Panduan Role:</strong><br>
            <span class="text-muted">
              <b>Admin</b> — akses penuh semua fitur &nbsp;|&nbsp;
              <b>HR Manager</b> — input & verifikasi penilaian &nbsp;|&nbsp;
              <b>Manajer</b> — penilaian unit sendiri &nbsp;|&nbsp;
              <b>Pegawai</b> — lihat nilai & isi rubrik sendiri
            </span>
          </div>
        </div>

      </div>
      <hr class="my-3">
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary btn-sm px-4">
          <i class="ti ti-device-floppy me-1"></i>
          <?= $user ? 'Update' : 'Simpan' ?>
        </button>
        <a href="<?= base_url('master/users') ?>"
           class="btn btn-light btn-sm px-4 border">Batal</a>
      </div>
    </form>
  </div>
</div>
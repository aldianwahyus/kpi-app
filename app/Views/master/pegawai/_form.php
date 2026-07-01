<div class="d-flex align-items-center gap-2 mb-3">
  <a href="<?= base_url('pegawai') ?>" class="btn btn-sm btn-light border">
    <i class="ti ti-arrow-left"></i>
  </a>
  <h5 class="mb-0 fw-semibold" style="color:#1F4E79">
    <?= $pegawai ? 'Edit Pegawai' : 'Tambah Pegawai Baru' ?>
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

<form action="<?= $action ?>" method="post">
  <?= csrf_field() ?>
  <div class="row g-3">

    <!-- DATA PEGAWAI -->
    <div class="col-md-8">
      <div class="card border-0 shadow-sm">
        <div class="card-header py-2" style="background:#E6F1FB">
          <span class="fw-semibold" style="color:#1F4E79;font-size:13px">
            <i class="ti ti-user me-1"></i> Data Pegawai
          </span>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold small">
                Nama Lengkap <span class="text-danger">*</span>
              </label>
              <input type="text" name="nama" class="form-control form-control-sm"
                     value="<?= old('nama', $pegawai['nama'] ?? '') ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small">NIP</label>
              <input type="text" name="nip" class="form-control form-control-sm"
                     value="<?= old('nip', $pegawai['nip'] ?? '') ?>"
                     placeholder="Nomor Induk Pegawai">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small">
                Divisi <span class="text-danger">*</span>
              </label>
              <select name="divisi_id" class="form-select form-select-sm" required>
                <option value="">-- Pilih Divisi --</option>
                <?php foreach ($divisi_dd as $id => $nama): ?>
                  <option value="<?= $id ?>"
                    <?= old('divisi_id', $pegawai['divisi_id'] ?? '') == $id ? 'selected' : '' ?>>
                    <?= esc($nama) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small">Jabatan</label>
              <input type="text" name="jabatan" class="form-control form-control-sm"
                     value="<?= old('jabatan', $pegawai['jabatan'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small">Unit / Bagian</label>
              <input type="text" name="unit" class="form-control form-control-sm"
                     value="<?= old('unit', $pegawai['unit'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small">Golongan</label>
              <input type="text" name="golongan" class="form-control form-control-sm"
                     value="<?= old('golongan', $pegawai['golongan'] ?? '') ?>"
                     placeholder="misal: III/A">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small">Tanggal Masuk</label>
              <input type="date" name="tgl_masuk" class="form-control form-control-sm"
                     value="<?= old('tgl_masuk', $pegawai['tgl_masuk'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold small">Atasan Langsung</label>
              <select name="atasan_id" class="form-select form-select-sm">
                <option value="">-- Tidak ada / Pilih --</option>
                <?php foreach ($atasan_dd as $id => $nama): ?>
                  <option value="<?= $id ?>"
                    <?= old('atasan_id', $pegawai['atasan_id'] ?? '') == $id ? 'selected' : '' ?>>
                    <?= esc($nama) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- AKUN USER -->
    <div class="col-md-4">
      <div class="card border-0 shadow-sm">
        <div class="card-header py-2" style="background:#EAF3DE">
          <span class="fw-semibold" style="color:#375623;font-size:13px">
            <i class="ti ti-lock me-1"></i> Akun Login
          </span>
        </div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label fw-semibold small">Email</label>
            <input type="email" name="email" class="form-control form-control-sm"
                   value="<?= old('email', $user['email'] ?? '') ?>"
                   placeholder="email@domain.com">
            <small class="text-muted" style="font-size:11px">
              Kosongkan jika tidak perlu akun login
            </small>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold small">
              Password
              <?= $pegawai ? '<small class="text-muted">(kosongkan jika tidak diubah)</small>' : '' ?>
            </label>
            <input type="password" name="password" class="form-control form-control-sm"
                   placeholder="<?= $pegawai ? 'Isi jika ingin ubah password' : 'Default: pegawai123' ?>">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold small">Role</label>
            <select name="role" class="form-select form-select-sm">
              <?php foreach (['pegawai'=>'Pegawai','drafter'=>'Drafter','approver'=>'Approver','hr'=>'HR Manager','admin'=>'Admin'] as $val => $lbl): ?>
                <option value="<?= $val ?>"
                  <?= old('role', $user['role'] ?? 'pegawai') === $val ? 'selected' : '' ?>>
                  <?= $lbl ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php if ($pegawai && isset($user)): ?>
            <div class="alert alert-info py-2" style="font-size:11px">
              <i class="ti ti-info-circle me-1"></i>
              Akun sudah ada: <strong><?= esc($user['email']) ?></strong>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </div>

  <div class="d-flex gap-2 mt-3">
    <button type="submit" class="btn btn-primary btn-sm px-4">
      <i class="ti ti-device-floppy me-1"></i>
      <?= $pegawai ? 'Update' : 'Simpan' ?>
    </button>
    <a href="<?= base_url('pegawai') ?>"
       class="btn btn-light btn-sm px-4 border">Batal</a>
  </div>
</form>
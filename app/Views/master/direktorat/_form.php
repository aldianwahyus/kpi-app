<div class="d-flex align-items-center gap-2 mb-3">
  <a href="<?= base_url('master/direktorat') ?>"
     class="btn btn-sm btn-light border">
    <i class="ti ti-arrow-left"></i>
  </a>
  <h5 class="mb-0 fw-semibold" style="color:#1F4E79">
    <?= $dir ? 'Edit Direktorat' : 'Tambah Direktorat' ?>
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

<div class="card border-0 shadow-sm" style="max-width:500px">
  <div class="card-body">
    <form action="<?= $action ?>" method="post">
      <?= csrf_field() ?>
      <div class="row g-3">
        <div class="col-md-5">
          <label class="form-label fw-semibold small">
            Kode <span class="text-danger">*</span>
          </label>
          <input type="text" name="kode" class="form-control form-control-sm"
                 value="<?= old('kode', $dir['kode'] ?? '') ?>"
                 placeholder="DIR-UTAMA" required>
        </div>
        <div class="col-md-7">
          <label class="form-label fw-semibold small">Singkatan</label>
          <input type="text" name="singkatan" class="form-control form-control-sm"
                 value="<?= old('singkatan', $dir['singkatan'] ?? '') ?>"
                 placeholder="DIR-UTAMA">
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold small">
            Nama Direktorat <span class="text-danger">*</span>
          </label>
          <input type="text" name="nama" class="form-control form-control-sm"
                 value="<?= old('nama', $dir['nama'] ?? '') ?>"
                 placeholder="Direktorat Utama" required>
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold small">Deskripsi</label>
          <textarea name="deskripsi" class="form-control form-control-sm"
                    rows="3"><?= old('deskripsi', $dir['deskripsi'] ?? '') ?></textarea>
        </div>
      </div>
      <hr class="my-3">
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary btn-sm px-4">
          <i class="ti ti-device-floppy me-1"></i>
          <?= $dir ? 'Update' : 'Simpan' ?>
        </button>
        <a href="<?= base_url('master/direktorat') ?>"
           class="btn btn-light btn-sm px-4 border">Batal</a>
      </div>
    </form>
  </div>
</div>
<div class="d-flex align-items-center gap-2 mb-3">
  <a href="<?= base_url('master/unit-kerja') ?>"
     class="btn btn-sm btn-light border">
    <i class="ti ti-arrow-left"></i>
  </a>
  <h5 class="mb-0 fw-semibold" style="color:#1F4E79">
    <?= $divisi ? 'Edit Unit Kerja' : 'Tambah Unit Kerja Baru' ?>
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
            Direktorat <span class="text-danger">*</span>
          </label>
          <select name="direktorat_id" class="form-select form-select-sm" required>
            <option value="">-- Pilih Direktorat --</option>
            <?php foreach ($direktorats as $id => $nama): ?>
              <option value="<?= $id ?>"
                <?= old('direktorat_id', $divisi['direktorat_id'] ?? '') == $id
                    ? 'selected' : '' ?>>
                <?= esc($nama) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-5">
          <label class="form-label fw-semibold small">
            Kode <span class="text-danger">*</span>
          </label>
          <input type="text" name="kode" class="form-control form-control-sm"
                 value="<?= old('kode', $divisi['kode'] ?? '') ?>"
                 placeholder="DIV-HCD" required>
        </div>

        <div class="col-md-7">
          <label class="form-label fw-semibold small">
            Nama Unit Kerja <span class="text-danger">*</span>
          </label>
          <input type="text" name="nama" class="form-control form-control-sm"
                 value="<?= old('nama', $divisi['nama'] ?? '') ?>" required>
        </div>

        <div class="col-12">
          <label class="form-label fw-semibold small">
            Kepala / Penanggung Jawab
          </label>
          <input type="text" name="kepala_divisi"
                 class="form-control form-control-sm"
                 value="<?= old('kepala_divisi', $divisi['kepala_divisi'] ?? '') ?>"
                 placeholder="Nama Kepala Divisi / PIC">
        </div>

        <div class="col-12">
          <label class="form-label fw-semibold small">Deskripsi</label>
          <textarea name="deskripsi" class="form-control form-control-sm"
                    rows="2"
                    placeholder="Deskripsi singkat unit kerja"><?= old('deskripsi', $divisi['deskripsi'] ?? '') ?></textarea>
        </div>

        <div class="col-12">
          <div class="p-3 rounded"
               style="background:#f8fafc;border:1px solid #e5e7eb;font-size:12px">
            <i class="ti ti-info-circle me-1" style="color:#2E75B6"></i>
            <strong>Format Kode:</strong>
            Divisi: <code>DIV-XXX</code> &nbsp;|&nbsp;
            Desk: <code>DESK-XXX</code> &nbsp;|&nbsp;
            KC: <code>KC-XXX</code> &nbsp;|&nbsp;
            KCP: <code>KCP-XXX</code> &nbsp;|&nbsp;
            SEVP: <code>SEVP-XXX</code>
          </div>
        </div>

      </div>
      <hr class="my-3">
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary btn-sm px-4">
          <i class="ti ti-device-floppy me-1"></i>
          <?= $divisi ? 'Update' : 'Simpan' ?>
        </button>
        <a href="<?= base_url('master/unit-kerja') ?>"
           class="btn btn-light btn-sm px-4 border">Batal</a>
      </div>
    </form>
  </div>
</div>
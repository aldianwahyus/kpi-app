<div class="d-flex align-items-center gap-2 mb-3">
  <a href="<?= base_url('master/periode') ?>"
     class="btn btn-sm btn-light border">
    <i class="ti ti-arrow-left"></i>
  </a>
  <h5 class="mb-0 fw-semibold" style="color:#1F4E79">
    <?= $periode ? 'Edit Periode' : 'Buat Periode Baru' ?>
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

<div class="card border-0 shadow-sm" style="max-width:600px">
  <div class="card-body">
    <form action="<?= $action ?>" method="post">
      <?= csrf_field() ?>
      <div class="row g-3">

        <div class="col-12">
          <label class="form-label fw-semibold small">
            Nama Periode <span class="text-danger">*</span>
          </label>
          <input type="text" name="nama" class="form-control form-control-sm"
                 value="<?= old('nama', $periode['nama'] ?? '') ?>"
                 placeholder="misal: Penilaian Q1 2025" required>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold small">
            Kode <span class="text-danger">*</span>
            <small class="text-muted">(unik, tidak bisa diubah)</small>
          </label>
          <input type="text" name="kode" class="form-control form-control-sm"
                 value="<?= old('kode', $periode['kode'] ?? '') ?>"
                 placeholder="misal: 2025-Q1"
                 <?= $periode ? 'readonly' : '' ?> required>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold small">Status</label>
          <select name="status" class="form-select form-select-sm">
            <?php foreach (['draft'=>'Draft','aktif'=>'Aktif','tutup'=>'Tutup'] as $val => $lbl): ?>
              <option value="<?= $val ?>"
                <?= old('status', $periode['status'] ?? 'draft') === $val ? 'selected' : '' ?>>
                <?= $lbl ?>
              </option>
            <?php endforeach; ?>
          </select>
          <small class="text-muted" style="font-size:11px">
            Hanya 1 periode yang boleh berstatus Aktif
          </small>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold small">
            Jenis Periode <span class="text-danger">*</span>
          </label>
          <select name="jenis" class="form-select form-select-sm" required>
            <?php foreach (['bulanan'=>'Bulanan (1 bulan)','triwulan'=>'Triwulan (3 bulan)','semester'=>'Semester (6 bulan)','tahunan'=>'Tahunan (12 bulan)'] as $val => $lbl): ?>
              <option value="<?= $val ?>"
                <?= old('jenis', $periode['jenis'] ?? 'bulanan') === $val ? 'selected' : '' ?>>
                <?= $lbl ?>
              </option>
            <?php endforeach; ?>
          </select>
          <small class="text-muted" style="font-size:11px">
            Menentukan bulan mana dari Master Target yang dipakai/dirata-rata
          </small>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold small">
            Tanggal Mulai <span class="text-danger">*</span>
          </label>
          <input type="date" name="tgl_mulai" class="form-control form-control-sm"
                 value="<?= old('tgl_mulai', $periode['tgl_mulai'] ?? '') ?>" required>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold small">
            Tanggal Selesai <span class="text-danger">*</span>
          </label>
          <input type="date" name="tgl_selesai" class="form-control form-control-sm"
                 value="<?= old('tgl_selesai', $periode['tgl_selesai'] ?? '') ?>" required>
        </div>

        <!-- Preview kode otomatis -->
        <div class="col-12">
          <div class="p-3 rounded" style="background:#f8fafc;border:1px solid #e5e7eb;font-size:12px">
            <i class="ti ti-info-circle me-1" style="color:#2E75B6"></i>
            <strong>Tips format kode:</strong>
            Tahunan: <code>2025</code> &nbsp;|&nbsp;
            Kuartalan: <code>2025-Q1</code> &nbsp;|&nbsp;
            Semesteran: <code>2025-S1</code> &nbsp;|&nbsp;
            Bulanan: <code>2025-01</code>
            <br>
            <strong>Penting:</strong> rentang Tanggal Mulai–Selesai harus mencakup
            jumlah bulan kalender yang sesuai dengan Jenis Periode yang dipilih
            (Bulanan=1, Triwulan=3, Semester=6, Tahunan=12).
          </div>
        </div>

      </div>

      <hr class="my-3">
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary btn-sm px-4">
          <i class="ti ti-device-floppy me-1"></i>
          <?= $periode ? 'Update' : 'Buat Periode' ?>
        </button>
        <a href="<?= base_url('master/periode') ?>"
           class="btn btn-light btn-sm px-4 border">Batal</a>
      </div>
    </form>
  </div>
</div>
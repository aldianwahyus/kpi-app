<?php $pegawai = $pegawai ?? null; $pegawaiId = $pegawaiId ?? 0; $tahun = $tahun ?? date('Y'); ?>
<div class="d-flex align-items-center gap-2 mb-3">
  <a href="<?= base_url('master-target') ?>" class="btn btn-sm btn-light border">
    <i class="ti ti-arrow-left"></i>
  </a>
  <div>
    <h5 class="mb-0 fw-semibold" style="color:#1F4E79">
      <i class="ti ti-file-import me-1"></i> Import Master Target
    </h5>
    <small class="text-muted">Upload file Excel untuk mengisi Target &amp; Bobot secara massal</small>
  </div>
</div>

<?php if ($pegawai): ?>
<div class="alert py-2 mb-3 d-flex align-items-center gap-2" style="background:#EAF3DE;border:1px solid #70AD47;font-size:13px">
  <i class="ti ti-user-check" style="color:#375623"></i>
  <span style="color:#375623">
    Template untuk pegawai: <strong><?= esc($pegawai['nama']) ?></strong> (Tahun <?= (int)$tahun ?>) —
    parameter KPI &amp; Parameter Turunan sudah otomatis terisi, tinggal lengkapi Bobot &amp; Target.
  </span>
</div>
<?php endif; ?>

<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="stat-card text-center">
      <div style="font-size:32px;color:#2E75B6;margin-bottom:8px"><i class="ti ti-download"></i></div>
      <div class="fw-semibold" style="color:#1F4E79">Step 1</div>
      <div style="font-size:13px;color:#555;margin:4px 0">Download template Excel</div>
      <a href="<?= base_url('master-target/import-template' . ($pegawaiId ? "?pegawai_id={$pegawaiId}&tahun={$tahun}" : '')) ?>"
         class="btn btn-outline-primary btn-sm mt-2">
        <i class="ti ti-file-spreadsheet me-1"></i> Download Template
      </a>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card text-center">
      <div style="font-size:32px;color:#375623;margin-bottom:8px"><i class="ti ti-table"></i></div>
      <div class="fw-semibold" style="color:#1F4E79">Step 2</div>
      <?php if ($pegawai): ?>
        <div style="font-size:13px;color:#555;margin:4px 0">Tinggal isi Bobot &amp; Target — parameter sudah otomatis terisi</div>
        <div style="font-size:11px;color:#888">Tidak perlu mengetik ulang Kode KPI/Nama Turunan</div>
      <?php else: ?>
        <div style="font-size:13px;color:#555;margin:4px 0">Isi Target 12 bulan &amp; Bobot di template</div>
        <div style="font-size:11px;color:#888">Kode KPI harus sudah di-assign ke pegawai di KPI Per Pegawai</div>
      <?php endif; ?>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card text-center">
      <div style="font-size:32px;color:#BF9000;margin-bottom:8px"><i class="ti ti-upload"></i></div>
      <div class="fw-semibold" style="color:#1F4E79">Step 3</div>
      <div style="font-size:13px;color:#555;margin:4px 0">Upload &amp; proses import</div>
    </div>
  </div>
</div>

<div class="alert d-flex gap-2 py-2 mb-3"
     style="background:#FFF3CD;border:1px solid #BF9000;font-size:13px">
  <i class="ti ti-alert-triangle" style="color:#BF9000;font-size:18px;flex-shrink:0"></i>
  <div style="color:#7F6000">
    <strong>Perhatian:</strong>
    <ul class="mb-0 mt-1 ps-3">
      <li>Gunakan NIP atau Email pegawai yang terdaftar di sistem</li>
      <li>Kode KPI &amp; Nama Turunan harus sudah ada (di-assign) di menu <strong>KPI Per Pegawai</strong> — import ini hanya mengisi Target/Bobot, tidak membuat parameter baru</li>
      <li>Kolom <strong>"Nama Parameter KPI"</strong> hanya informasi (memudahkan identifikasi) — tidak dibaca saat import, boleh diubah/dikosongkan bebas</li>
      <li>Baris <strong>TURUNAN</strong> harus berada tepat di bawah baris <strong>INDUK</strong>-nya</li>
      <li>Bobot untuk KPI Induk yang sudah punya Turunan <strong>tidak perlu diisi</strong> — dihitung otomatis dari SUM Bobot Turunannya</li>
      <li>Kolom Target boleh dikosongkan sebagian — hanya bulan yang diisi yang akan diperbarui, bulan lain tidak tersentuh</li>
      <li>Target diabaikan untuk parameter berpolarity Special Scoring</li>
      <li>Setelah import, periksa Total Bobot setiap pegawai (per tahun) harus mencapai 100% di layar Master Target</li>
    </ul>
  </div>
</div>

<div class="card border-0 shadow-sm" style="max-width:550px">
  <div class="card-body">
    <form action="<?= base_url('master-target/import') ?>" method="post"
          enctype="multipart/form-data">
      <?= csrf_field() ?>
      <label class="form-label fw-semibold small">
        File Excel (.xlsx) <span class="text-danger">*</span>
      </label>
      <input type="file" name="file_excel" class="form-control form-control-sm"
             accept=".xlsx,.xls" required>
      <hr class="my-3">
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary btn-sm px-4">
          <i class="ti ti-upload me-1"></i> Proses Import
        </button>
        <a href="<?= base_url('master-target') ?>"
           class="btn btn-light btn-sm px-4 border">Batal</a>
      </div>
    </form>
  </div>
</div>

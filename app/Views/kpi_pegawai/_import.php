<div class="d-flex align-items-center gap-2 mb-3">
  <a href="<?= base_url('kpi-pegawai') ?>" class="btn btn-sm btn-light border">
    <i class="ti ti-arrow-left"></i>
  </a>
  <div>
    <h5 class="mb-0 fw-semibold" style="color:#1F4E79">
      <i class="ti ti-file-import me-1"></i> Import KPI Per Pegawai
    </h5>
    <small class="text-muted">Upload file Excel untuk mengisi KPI pegawai secara massal</small>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="stat-card text-center">
      <div style="font-size:32px;color:#2E75B6;margin-bottom:8px"><i class="ti ti-download"></i></div>
      <div class="fw-semibold" style="color:#1F4E79">Step 1</div>
      <div style="font-size:13px;color:#555;margin:4px 0">Download template Excel</div>
      <a href="<?= base_url('kpi-pegawai/import-template') ?>"
         class="btn btn-outline-primary btn-sm mt-2">
        <i class="ti ti-file-spreadsheet me-1"></i> Download Template
      </a>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card text-center">
      <div style="font-size:32px;color:#375623;margin-bottom:8px"><i class="ti ti-table"></i></div>
      <div class="fw-semibold" style="color:#1F4E79">Step 2</div>
      <div style="font-size:13px;color:#555;margin:4px 0">Isi data KPI di template</div>
      <div style="font-size:11px;color:#888">Gunakan Kode KPI yang sesuai Master KPI Unit</div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card text-center">
      <div style="font-size:32px;color:#BF9000;margin-bottom:8px"><i class="ti ti-upload"></i></div>
      <div class="fw-semibold" style="color:#1F4E79">Step 3</div>
      <div style="font-size:13px;color:#555;margin:4px 0">Upload & proses import</div>
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
      <li>Kode KPI harus sesuai dengan Master KPI Unit yang sudah ada (contoh: MR-F1)</li>
      <li>Baris <strong>TURUNAN</strong> harus berada tepat di bawah baris <strong>INDUK</strong>-nya</li>
      <li>KPI yang sudah ada untuk pegawai tersebut akan <strong>dilewati (Skip)</strong> — data lama aman</li>
      <li>Setelah import, periksa total Bobot setiap pegawai harus mencapai 100%</li>
    </ul>
  </div>
</div>

<div class="card border-0 shadow-sm" style="max-width:550px">
  <div class="card-body">
    <form action="<?= base_url('kpi-pegawai/import') ?>" method="post"
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
        <a href="<?= base_url('kpi-pegawai') ?>"
           class="btn btn-light btn-sm px-4 border">Batal</a>
      </div>
    </form>
  </div>
</div>
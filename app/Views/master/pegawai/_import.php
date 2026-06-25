<div class="d-flex align-items-center gap-2 mb-3">
  <a href="<?= base_url('pegawai') ?>"
     class="btn btn-sm btn-light border">
    <i class="ti ti-arrow-left"></i>
  </a>
  <h5 class="mb-0 fw-semibold" style="color:#1F4E79">
    <i class="ti ti-file-import me-1"></i> Import Data Pegawai
  </h5>
</div>

<!-- Langkah-langkah -->
<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="stat-card text-center">
      <div style="font-size:32px;color:#2E75B6;margin-bottom:8px">
        <i class="ti ti-download"></i>
      </div>
      <div class="fw-semibold" style="color:#1F4E79">Step 1</div>
      <div style="font-size:13px;color:#555;margin:4px 0">
        Download template Excel
      </div>
      <a href="<?= base_url('pegawai/template-import') ?>"
         class="btn btn-outline-primary btn-sm mt-2">
        <i class="ti ti-file-spreadsheet me-1"></i> Download Template
      </a>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card text-center">
      <div style="font-size:32px;color:#375623;margin-bottom:8px">
        <i class="ti ti-table"></i>
      </div>
      <div class="fw-semibold" style="color:#1F4E79">Step 2</div>
      <div style="font-size:13px;color:#555;margin:4px 0">
        Isi data pegawai di template
      </div>
      <div style="font-size:11px;color:#888">
        Lihat sheet "Referensi Divisi" untuk kode divisi
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card text-center">
      <div style="font-size:32px;color:#BF9000;margin-bottom:8px">
        <i class="ti ti-upload"></i>
      </div>
      <div class="fw-semibold" style="color:#1F4E79">Step 3</div>
      <div style="font-size:13px;color:#555;margin:4px 0">
        Upload file & proses import
      </div>
    </div>
  </div>
</div>

<!-- Catatan penting -->
<div class="alert d-flex gap-2 py-2 mb-3"
     style="background:#FFF3CD;border:1px solid #BF9000;font-size:13px">
  <i class="ti ti-alert-triangle" style="color:#BF9000;font-size:18px;flex-shrink:0"></i>
  <div style="color:#7F6000">
    <strong>Perhatian:</strong>
    <ul class="mb-0 mt-1" style="padding-left:16px">
      <li>Kolom <strong>Nama</strong> dan <strong>Kode Divisi</strong> wajib diisi</li>
      <li>Kode Divisi harus sesuai dengan yang ada di Master Data Unit Kerja</li>
      <li>NIP yang sudah terdaftar akan dilewati (tidak di-overwrite)</li>
      <li>Email yang sudah terdaftar sebagai user tidak akan dibuat ulang</li>
      <li>Jika kolom Email diisi, akun login otomatis dibuat</li>
    </ul>
  </div>
</div>

<!-- Error dari import sebelumnya -->
<?php if (session()->getFlashdata('import_errors')): ?>
<div class="alert alert-warning py-2 mb-3" style="font-size:13px">
  <strong>Detail baris yang gagal:</strong>
  <ul class="mb-0 mt-1" style="padding-left:16px">
    <?php foreach (session()->getFlashdata('import_errors') as $err): ?>
      <li><?= esc($err) ?></li>
    <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<!-- Form upload -->
<div class="card border-0 shadow-sm" style="max-width:500px">
  <div class="card-body">
    <form action="<?= base_url('pegawai/import-process') ?>"
          method="post"
          enctype="multipart/form-data">
      <?= csrf_field() ?>

      <div class="mb-3">
        <label class="form-label fw-semibold small">
          Upload File Excel
          <span class="text-danger">*</span>
        </label>
        <input type="file" name="file_excel"
               class="form-control form-control-sm"
               accept=".xlsx,.xls" required>
        <div class="form-text" style="font-size:11px">
          Format: .xlsx atau .xls | Maksimal: 5MB
        </div>
      </div>

      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary btn-sm px-4"
                onclick="this.disabled=true;this.innerHTML='<i class=\'ti ti-loader\'></i> Memproses...';this.form.submit()">
          <i class="ti ti-upload me-1"></i> Proses Import
        </button>
        <a href="<?= base_url('pegawai') ?>"
           class="btn btn-light btn-sm px-4 border">Batal</a>
      </div>
    </form>
  </div>
</div>
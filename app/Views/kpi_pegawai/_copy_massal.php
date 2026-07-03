<div class="d-flex align-items-center gap-2 mb-3">
  <a href="<?= base_url('kpi-pegawai') ?>" class="btn btn-sm btn-light border">
    <i class="ti ti-arrow-left"></i>
  </a>
  <div>
    <h5 class="mb-0 fw-semibold" style="color:#1F4E79">
      <i class="ti ti-copy me-1"></i> Salin KPI Massal per Divisi
    </h5>
    <small class="text-muted">Salin seluruh konfigurasi KPI dari satu pegawai ke semua pegawai di satu divisi</small>
  </div>
</div>

<div class="alert d-flex gap-2 py-2 mb-3"
     style="background:#E6F1FB;border:1px solid #2E75B6;font-size:13px">
  <i class="ti ti-info-circle" style="color:#2E75B6;font-size:18px;flex-shrink:0"></i>
  <div style="color:#1F4E79">
    <strong>Cara kerja Salin Massal:</strong>
    <ul class="mb-0 mt-1 ps-3">
      <li>Seluruh KPI (Induk + Parameter Turunan) dari pegawai sumber akan disalin</li>
      <li>Pegawai tujuan yang sudah memiliki KPI yang sama akan <strong>dilewati (Skip)</strong></li>
      <li>Pegawai sumber sendiri <strong>dikecualikan</strong> dari daftar tujuan</li>
      <li>Fitur ini <strong>tidak menghapus</strong> KPI yang sudah ada pada pegawai tujuan</li>
    </ul>
  </div>
</div>

<div class="card border-0 shadow-sm" style="max-width:600px">
  <div class="card-body">
    <form action="<?= base_url('kpi-pegawai/copy-massal') ?>" method="post"
          onsubmit="return confirmAction(event, {
            title: 'Konfirmasi Salin Massal',
            text: 'KPI akan disalin ke semua pegawai aktif di divisi yang dipilih. Lanjutkan?',
            icon: 'question',
            confirmText: 'Ya, Salin'
          })">
      <?= csrf_field() ?>

      <div class="mb-3">
        <label class="form-label fw-semibold small">
          Pegawai Sumber <span class="text-danger">*</span>
          <span class="text-muted fw-normal" style="font-size:10px">
            — KPI dari pegawai ini yang akan disalin
          </span>
        </label>
        <select name="source_pegawai_id" class="form-select form-select-sm" required
                id="sel-sumber">
          <option value="">-- Pilih Pegawai Sumber --</option>
          <?php
          $grouped = [];
          foreach ($pegawaiList as $p) {
              $divisiNama = $p['nama_divisi'] ?? $p['divisi'] ?? 'Tanpa Divisi';
              $grouped[$divisiNama][] = $p;
          }
          foreach ($grouped as $divisiNama => $list):
          ?>
          <optgroup label="<?= esc($divisiNama) ?>">
            <?php foreach ($list as $p): ?>
            <option value="<?= $p['id'] ?>">
              <?= esc($p['nama']) ?>
              <?= $p['jabatan'] ? ' — ' . esc($p['jabatan']) : '' ?>
            </option>
            <?php endforeach; ?>
          </optgroup>
          <?php endforeach; ?>
        </select>
        <div class="form-text" style="font-size:10px">
          Pastikan pegawai sumber sudah memiliki konfigurasi KPI yang lengkap (Total Bobot = 100%)
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold small">
          Divisi Tujuan <span class="text-danger">*</span>
          <span class="text-muted fw-normal" style="font-size:10px">
            — seluruh pegawai aktif di divisi ini (kecuali pegawai sumber) akan menerima salinan KPI
          </span>
        </label>
        <select name="divisi_id" class="form-select form-select-sm" required id="sel-divisi">
          <option value="">-- Pilih Divisi Tujuan --</option>
          <?php foreach ($divisiList as $d): ?>
          <option value="<?= $d['id'] ?>"
                  data-count="<?= $d['jumlah_pegawai'] ?? '?' ?>">
            <?= esc($d['nama']) ?>
          </option>
          <?php endforeach; ?>
        </select>
        <div class="form-text" style="font-size:11px" id="info-divisi-count"></div>
      </div>

      <hr class="my-3">
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary btn-sm px-4">
          <i class="ti ti-copy me-1"></i> Salin Massal Sekarang
        </button>
        <a href="<?= base_url('kpi-pegawai') ?>"
           class="btn btn-light btn-sm px-4 border">Batal</a>
      </div>
    </form>
  </div>
</div>

<script>
document.getElementById('sel-divisi').addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    const info = document.getElementById('info-divisi-count');
    if (opt.value) {
        const count = opt.getAttribute('data-count');
        info.textContent = count !== '?'
            ? `Estimasi ${count} pegawai di divisi ini yang akan menerima salinan KPI (pegawai sumber dikecualikan).`
            : 'Pilih divisi untuk melihat jumlah pegawai.';
        info.style.color = '#1F4E79';
    } else {
        info.textContent = '';
    }
});
</script>
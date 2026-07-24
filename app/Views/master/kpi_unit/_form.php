<div class="d-flex align-items-center gap-2 mb-3">
  <a href="<?= base_url("master/kpi-unit/{$direktorat['id']}") ?>"
     class="btn btn-sm btn-light border">
    <i class="ti ti-arrow-left"></i>
  </a>
  <h5 class="mb-0 fw-semibold" style="color:#1F4E79">
    <?= $kpi ? 'Edit KPI Unit' : 'Tambah KPI Unit' ?>
    <small class="text-muted fw-normal" style="font-size:13px">
      — <?= esc($direktorat['nama']) ?>
    </small>
  </h5>
</div>
<?php if (session()->getFlashdata('errors')): ?>
  <div class="alert alert-danger py-2" style="font-size:13px">
    <ul class="mb-0">
      <?php foreach (session()->getFlashdata('errors') as $err): ?>
        <li><?= esc($err) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="card border-0 shadow-sm" style="max-width:650px">
  <div class="card-body">
    <form action="<?= $action ?>" method="post">
      <?= csrf_field() ?>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-semibold small">
            Perspektif <span class="text-danger">*</span>
          </label>
          <?php if ($kpi): ?>
            <input type="text" class="form-control form-control-sm" value="<?= esc($kpi['perspektif']) ?>" disabled style="background:#f8f9fa">
            <input type="hidden" name="perspektif" value="<?= esc($kpi['perspektif']) ?>">
          <?php else: ?>
            <select name="perspektif" id="sel-perspektif" class="form-select form-select-sm" required>
              <option value="">-- Pilih Perspektif --</option>
              <?php foreach (['Financial','Customer','Internal Process','Learning & Growth'] as $p): ?>
                <option value="<?= $p ?>" <?= old('perspektif') === $p ? 'selected' : '' ?>><?= $p ?></option>
              <?php endforeach; ?>
            </select>
          <?php endif; ?>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold small">
            Kode
            <?php if (!$kpi): ?>
              <span class="text-muted" style="font-size:10px;font-weight:400">— otomatis</span>
            <?php endif; ?>
          </label>
          <div class="input-group input-group-sm">
            <input type="text" name="kode" id="input-kode" class="form-control"
                   value="<?= esc($kpi['kode'] ?? old('kode', '')) ?>"
                   placeholder="Pilih perspektif dahulu"
                   readonly style="background:#f8f9fa;font-family:monospace;font-weight:600;color:#1F4E79">
          </div>
          <div id="kode-info" class="form-text" style="font-size:10px;color:#1F4E79"></div>
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold small">
            Nama KPI <span class="text-danger">*</span>
          </label>
          <input type="text" name="nama_kpi" class="form-control form-control-sm"
                 value="<?= old('nama_kpi', $kpi['nama_kpi'] ?? '') ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold small">
            Satuan <span class="text-danger">*</span>
          </label>
          <input type="text" name="satuan" class="form-control form-control-sm"
                 value="<?= old('satuan', $kpi['satuan'] ?? '') ?>"
                 placeholder="%, Skor, Jumlah..." required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold small">Urutan</label>
          <input type="number" name="urutan" class="form-control form-control-sm"
                 value="<?= old('urutan', $kpi['urutan'] ?? 99) ?>">
        </div>
        <?php $curPolarity = old('polarity', $kpi['polarity'] ?? 'max'); ?>
        <div class="col-md-6">
          <label class="form-label fw-semibold small">Polarity</label>
          <select name="polarity" id="sel-polarity" class="form-select form-select-sm">
            <option value="max"        <?= $curPolarity === 'max'        ? 'selected' : '' ?>>↑ Maximize</option>
            <option value="min"        <?= $curPolarity === 'min'        ? 'selected' : '' ?>>↓ Minimize</option>
            <option value="precise"    <?= $curPolarity === 'precise'    ? 'selected' : '' ?>>◎ Precise is Better</option>
            <option value="special"    <?= $curPolarity === 'special'    ? 'selected' : '' ?>>⚑ Special Scoring</option>
            <option value="tertimbang" <?= $curPolarity === 'tertimbang' ? 'selected' : '' ?>>⚖ Scoring Tertimbang</option>
          </select>
        </div>

        <!-- Perubahan Polarity — otomatis mengikuti Polarity (Maximize =
             Realisasi/Target, Minimize = Target/Realisasi), tidak lagi
             diinput manual supaya tidak bisa terjadi kombinasi yang saling
             bertentangan (mis. Maximize tapi rumusnya kebalik). -->
        <div class="col-md-6 polarity-field" data-for="max,min">
          <label class="form-label fw-semibold small">Perubahan Polarity</label>
          <div class="form-control form-control-sm bg-light text-muted" style="font-size:12px" id="info-perubahan-polarity">
            <?= $curPolarity === 'min' ? 'Negatif → Target/Realisasi' : 'Positif → Realisasi/Target' ?>
          </div>
        </div>

        <!-- Precise is Better — 3 toleransi deviasi (%) simetris dari target,
             menaik: Skor4 < Skor3 < Skor2. Skor 1 = di luar toleransi Skor 2. -->
        <div class="col-12 polarity-field" data-for="precise">
          <div class="alert py-2 mb-2" style="font-size:11px;background:#E6F1FB;border:1px solid #2E75B6;color:#1F4E79">
            Toleransi deviasi (%) dari target (100%), berlaku simetris di atas & di bawah.
            Skor 1 otomatis berlaku di luar Toleransi Skor 2.
          </div>
          <div class="row g-2">
            <div class="col-md-4">
              <label class="form-label fw-semibold small">Toleransi Skor 4 (±%)</label>
              <input type="number" name="toleransi_skor4" class="form-control form-control-sm"
                     step="any" min="0" placeholder="2.5"
                     value="<?= esc(old('toleransi_skor4', $kpi['toleransi_skor4'] ?? '')) ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold small">Toleransi Skor 3 (±%)</label>
              <input type="number" name="toleransi_skor3" class="form-control form-control-sm"
                     step="any" min="0" placeholder="7.5"
                     value="<?= esc(old('toleransi_skor3', $kpi['toleransi_skor3'] ?? '')) ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold small">Toleransi Skor 2 (±%)</label>
              <input type="number" name="toleransi_skor2" class="form-control form-control-sm"
                     step="any" min="0" placeholder="12.5"
                     value="<?= esc(old('toleransi_skor2', $kpi['toleransi_skor2'] ?? '')) ?>">
            </div>
          </div>
        </div>

        <!-- Special Scoring — Sifat menentukan arah Skor untuk kejadian Ada/Tidak Ada -->
        <div class="col-md-6 polarity-field" data-for="special">
          <label class="form-label fw-semibold small">Sifat</label>
          <select name="sifat_khusus" class="form-select form-select-sm">
            <option value="maximize"
              <?= old('sifat_khusus', $kpi['sifat_khusus'] ?? 'maximize') === 'maximize' ? 'selected' : '' ?>>
              Maximize — (Contoh: Jika Ada/Terealisasi = Skor 4, Jika Tidak Ada/Tidak Terealisasi = Skor 1)
            </option>
            <option value="minimize"
              <?= old('sifat_khusus', $kpi['sifat_khusus'] ?? 'maximize') === 'minimize' ? 'selected' : '' ?>>
              Minimize — (Contoh: Jika Ada/Terjadi = Skor 1, Jika Tidak Ada/Tidak Terjadi = Skor 4)
            </option>
          </select>
        </div>

        <!-- Scoring Tertimbang — tidak butuh field tambahan di sini. Target
             Indikator 1 (Posisi Akhir) memakai Target KPI yang ada; Rata-rata
             Harian (Indikator 2) dimasukkan langsung sebagai persentase saat
             penginputan penilaian, bukan konfigurasi per-KPI. -->
        <div class="col-12 polarity-field" data-for="tertimbang">
          <div class="alert py-2 mb-0" style="font-size:11px;background:#E6F1FB;border:1px solid #2E75B6;color:#1F4E79">
            Skor Akhir = Skor Indikator (dari Realisasi/Target di atas) × Pengkali
            (dari persentase Rata-rata Harian yang diinput saat penilaian).
            Tidak ada konfigurasi tambahan yang perlu diisi di sini.
          </div>
        </div>
      </div>
      <hr class="my-3">
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary btn-sm px-4">
          <i class="ti ti-device-floppy me-1"></i>
          <?= $kpi ? 'Update' : 'Simpan' ?>
        </button>
        <a href="<?= base_url("master/kpi-unit/{$direktorat['id']}") ?>"
           class="btn btn-light btn-sm px-4 border">Batal</a>
      </div>
    </form>
  </div>
</div>
<?php if (!$kpi): ?>
<script>
(function () {
    const selPerspektif = document.getElementById('sel-perspektif');
    const inputKode      = document.getElementById('input-kode');
    const kodeInfo        = document.getElementById('kode-info');
    const direktoratId    = <?= (int)$direktorat['id'] ?>;

    if (!selPerspektif) return;

    selPerspektif.addEventListener('change', function () {
        const perspektif = this.value;
        if (!perspektif) {
            inputKode.value = '';
            kodeInfo.innerHTML = '';
            return;
        }

        inputKode.value = 'Memuat...';
        kodeInfo.innerHTML = '';

        const params = new URLSearchParams({
            direktorat_id: direktoratId,
            perspektif: perspektif
        });

        fetch('<?= base_url('master/kpi-unit/generate-kode') ?>?' + params.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(r => r.json())
            .then(data => {
                if (data.kode) {
                    inputKode.value = data.kode;
                    kodeInfo.innerHTML = data.preview || '';
                } else {
                    inputKode.value = '';
                    kodeInfo.innerHTML = '<span class="text-danger">' + (data.error || 'Gagal generate kode') + '</span>';
                }
            })
            .catch(() => {
                inputKode.value = '';
                kodeInfo.innerHTML = '<span class="text-danger">Gagal terhubung ke server.</span>';
            });
    });
})();
</script>
<?php endif; ?>
<script>
(function () {
    const selPolarity = document.getElementById('sel-polarity');
    if (!selPolarity) return;

    const infoPerubahan = document.getElementById('info-perubahan-polarity');

    function toggleFields() {
        const polarity = selPolarity.value;
        document.querySelectorAll('.polarity-field').forEach(function (el) {
            const forList = (el.getAttribute('data-for') || '').split(',');
            const active  = forList.includes(polarity);
            el.style.display = active ? '' : 'none';
            el.querySelectorAll('input, select').forEach(function (field) {
                field.disabled = !active;
            });
        });
        if (infoPerubahan) {
            infoPerubahan.textContent = polarity === 'min'
                ? 'Negatif → Target/Realisasi'
                : 'Positif → Realisasi/Target';
        }
    }

    selPolarity.addEventListener('change', toggleFields);
    toggleFields();
})();
</script>
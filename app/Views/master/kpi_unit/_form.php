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
        <div class="col-md-6">
          <label class="form-label fw-semibold small">Polarity</label>
          <select name="polarity" class="form-select form-select-sm">
            <option value="max"
              <?= old('polarity', $kpi['polarity'] ?? 'max') === 'max' ? 'selected' : '' ?>>
              ↑ Maximize
            </option>
            <option value="min"
              <?= old('polarity', $kpi['polarity'] ?? 'max') === 'min' ? 'selected' : '' ?>>
              ↓ Minimize
            </option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold small">Perubahan Polarity</label>
          <select name="perubahan_polarity" class="form-select form-select-sm">
            <option value="pos"
              <?= old('perubahan_polarity', $kpi['perubahan_polarity'] ?? 'pos') === 'pos' ? 'selected' : '' ?>>
              Positif → Real/Target
            </option>
            <option value="neg"
              <?= old('perubahan_polarity', $kpi['perubahan_polarity'] ?? 'pos') === 'neg' ? 'selected' : '' ?>>
              Negatif → Target/Real
            </option>
          </select>
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
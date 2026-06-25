<div class="d-flex align-items-center gap-2 mb-3">
  <a href="<?= base_url('master/kpi') ?>"
     class="btn btn-sm btn-light border">
    <i class="ti ti-arrow-left"></i>
  </a>
  <div>
    <h5 class="mb-0 fw-semibold" style="color:#1F4E79">
      <?= $kpi ? 'Edit KPI' : 'Tambah KPI Baru' ?>
    </h5>
  </div>
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

<div class="card border-0 shadow-sm">
  <div class="card-body">
    <form action="<?= $action ?>" method="post">
      <?= csrf_field() ?>

      <div class="row g-3">
        <!-- Perspektif -->
        <div class="col-md-4">
          <label class="form-label fw-semibold small">Perspektif <span class="text-danger">*</span></label>
          <select name="perspektif" class="form-select form-select-sm" required>
            <option value="">-- Pilih --</option>
            <?php foreach (['Financial','Customer','Internal Process','Learning & Growth'] as $p): ?>
              <option value="<?= $p ?>"
                <?= old('perspektif', $kpi['perspektif'] ?? '') === $p ? 'selected' : '' ?>>
                <?= $p ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Nama KPI -->
        <div class="col-md-8">
          <label class="form-label fw-semibold small">Nama KPI <span class="text-danger">*</span></label>
          <input type="text" name="nama_kpi" class="form-control form-control-sm"
                 value="<?= old('nama_kpi', $kpi['nama_kpi'] ?? '') ?>" required>
        </div>

        <!-- Kode -->
        <div class="col-md-3">
          <label class="form-label fw-semibold small">Kode KPI <span class="text-danger">*</span></label>
          <input type="text" name="kode" class="form-control form-control-sm"
                 value="<?= old('kode', $kpi['kode'] ?? '') ?>"
                 placeholder="misal: F4.1" required>
        </div>

        <!-- Satuan -->
        <div class="col-md-3">
          <label class="form-label fw-semibold small">Satuan <span class="text-danger">*</span></label>
          <input type="text" name="satuan" class="form-control form-control-sm"
                 value="<?= old('satuan', $kpi['satuan'] ?? '') ?>"
                 placeholder="%, Skor, Jumlah, dll" required>
        </div>

        <!-- Bobot -->
        <div class="col-md-3">
          <label class="form-label fw-semibold small">
            Bobot <span class="text-danger">*</span>
            <small class="text-muted">(desimal, misal: 0.10)</small>
          </label>
          <input type="number" name="bobot" class="form-control form-control-sm"
                 value="<?= old('bobot', $kpi['bobot'] ?? '') ?>"
                 step="0.001" min="0" max="1" required>
        </div>

        <!-- Urutan -->
        <div class="col-md-3">
          <label class="form-label fw-semibold small">Urutan</label>
          <input type="number" name="urutan" class="form-control form-control-sm"
                 value="<?= old('urutan', $kpi['urutan'] ?? 99) ?>"
                 min="1">
        </div>

        <!-- Polarity -->
        <div class="col-md-3">
          <label class="form-label fw-semibold small">Polarity <span class="text-danger">*</span></label>
          <select name="polarity" class="form-select form-select-sm" required>
            <option value="max" <?= old('polarity', $kpi['polarity'] ?? '') === 'max' ? 'selected' : '' ?>>
              ↑ Maximize (semakin besar semakin baik)
            </option>
            <option value="min" <?= old('polarity', $kpi['polarity'] ?? '') === 'min' ? 'selected' : '' ?>>
              ↓ Minimize (semakin kecil semakin baik)
            </option>
          </select>
        </div>

        <!-- Perubahan Polarity -->
        <div class="col-md-3">
          <label class="form-label fw-semibold small">Perubahan Polarity <span class="text-danger">*</span></label>
          <select name="perubahan_polarity" class="form-select form-select-sm" required>
            <option value="pos" <?= old('perubahan_polarity', $kpi['perubahan_polarity'] ?? '') === 'pos' ? 'selected' : '' ?>>
              Positif → Real / Target
            </option>
            <option value="neg" <?= old('perubahan_polarity', $kpi['perubahan_polarity'] ?? '') === 'neg' ? 'selected' : '' ?>>
              Negatif → Target / Real
            </option>
          </select>
        </div>

        <!-- Total Bobot Perspektif -->
        <div class="col-md-3">
          <label class="form-label fw-semibold small">
            Total Bobot Perspektif
            <small class="text-muted">(isi di baris pertama perspektif saja)</small>
          </label>
          <input type="number" name="total_bobot_perspektif"
                 class="form-control form-control-sm"
                 value="<?= old('total_bobot_perspektif', $kpi['total_bobot_perspektif'] ?? '') ?>"
                 step="0.01" min="0" max="1"
                 placeholder="misal: 0.25">
        </div>

        <!-- Rubrik Sheet -->
        <div class="col-md-3">
          <label class="form-label fw-semibold small">Rubrik Sheet</label>
          <select name="rubrik_sheet" class="form-select form-select-sm">
            <option value="">— Tidak ada —</option>
            <?php foreach (['esi','pelatihan','kompetensi','milestone'] as $rs): ?>
              <option value="<?= $rs ?>"
                <?= old('rubrik_sheet', $kpi['rubrik_sheet'] ?? '') === $rs ? 'selected' : '' ?>>
                <?= ucfirst($rs) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Is Kualitatif -->
        <div class="col-md-12">
          <div class="form-check">
            <input type="checkbox" name="is_kualitatif" id="is_kualitatif"
                   class="form-check-input" value="1"
                   <?= old('is_kualitatif', $kpi['is_kualitatif'] ?? 0) ? 'checked' : '' ?>>
            <label class="form-check-label small" for="is_kualitatif">
              KPI ini bersifat <strong>Kualitatif</strong>
              (nilai realisasi diambil dari rubrik/kuesioner)
            </label>
          </div>
        </div>
      </div>

      <hr class="my-3">
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary btn-sm px-4">
          <i class="ti ti-device-floppy me-1"></i>
          <?= $kpi ? 'Update KPI' : 'Simpan KPI' ?>
        </button>
        <a href="<?= base_url('master/kpi') ?>"
           class="btn btn-light btn-sm px-4 border">
          Batal
        </a>
      </div>
    </form>
  </div>
</div>
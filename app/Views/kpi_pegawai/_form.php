<div class="d-flex align-items-center gap-2 mb-3">
  <a href="<?= base_url('kpi-pegawai') ?>"
     class="btn btn-sm btn-light border">
    <i class="ti ti-arrow-left"></i>
  </a>
  <div class="flex-grow-1">
    <h5 class="mb-0 fw-semibold" style="color:#1F4E79">
      KPI Per Pegawai — <?= esc($pegawai['nama']) ?>
    </h5>
    <small class="text-muted">
      <?= esc($pegawai['jabatan'] ?? '') ?>
    </small>
  </div>
</div>

<!-- Total bobot indicator -->
<?php $bobot_pct = round($totalBobot * 100, 2); ?>
<div class="alert py-2 mb-3 d-flex align-items-center gap-2"
     id="alert-bobot"
     style="background:<?= $bobot_pct==100?'#E2EFDA':'#FCE4D6' ?>;
            border:1px solid <?= $bobot_pct==100?'#70AD47':'#C00000' ?>">
  <i class="ti ti-calculator"></i>
  <span style="font-size:13px">
    Total bobot KPI pegawai ini:
    <strong id="total-bobot-display"><?= $bobot_pct ?>%</strong>
    <?= $bobot_pct==100 ? '— ✓ Sudah tepat 100%' : '— Harus tepat 100%!' ?>
  </span>
</div>

<div class="row g-3">

  <!-- KOLOM KIRI: KPI yang sudah di-assign -->
  <div class="col-md-7">
    <div class="card border-0 shadow-sm">
      <div class="card-header py-2 d-flex align-items-center
                  justify-content-between"
           style="background:#E6F1FB">
        <span class="fw-semibold" style="color:#1F4E79;font-size:13px">
          <i class="ti ti-list-check me-1"></i>
          KPI <?= esc($pegawai['nama']) ?>
          <span class="badge bg-primary ms-1">
            <?= count($assigned) ?>
          </span>
        </span>

        <!-- Tombol copy dari pegawai lain -->
        <button type="button"
                class="btn btn-outline-secondary btn-sm"
                style="font-size:11px"
                data-bs-toggle="modal"
                data-bs-target="#modalCopy">
          <i class="ti ti-copy me-1"></i> Copy dari pegawai lain
        </button>
      </div>

      <div class="card-body p-0">
        <?php if (empty($assigned)): ?>
          <div class="text-center py-4 text-muted" style="font-size:13px">
            <i class="ti ti-playlist-x fs-2 d-block mb-1"></i>
            Belum ada KPI. Pilih dari daftar kanan.
          </div>
        <?php else: ?>

        <form action="<?= base_url("kpi-pegawai/save-bobot/{$pegawai['id']}") ?>"
              method="post">
          <?= csrf_field() ?>

          <?php
          $persp_colors = [
              'Financial'        => ['#E6F1FB','#1F4E79'],
              'Customer'         => ['#EAF3DE','#375623'],
              'Internal Process' => ['#FFF3CD','#7F6000'],
              'Learning & Growth'=> ['#F3E5F5','#5C2A6B'],
          ];
          ?>

          <?php foreach ($assignedGrouped as $perspektif => $kpis): ?>
          <?php $pc = $persp_colors[$perspektif] ?? ['#f8f9fa','#333']; ?>
          <div class="px-3 py-1"
               style="background:<?= $pc[0] ?>;
                      border-left:3px solid <?= $pc[1] ?>">
            <small class="fw-semibold" style="color:<?= $pc[1] ?>">
              <?= esc($perspektif) ?>
            </small>
          </div>

          <?php foreach ($kpis as $kpi): ?>
          <div class="d-flex align-items-center gap-2 px-3 py-2
                      border-bottom">
            <input type="hidden" name="kp_id[]"  value="<?= $kpi['id'] ?>">

            <div class="flex-grow-1">
              <div class="fw-semibold" style="font-size:13px">
                <?= esc($kpi['nama_kpi']) ?>
              </div>
              <div style="font-size:11px;color:#888">
                <code><?= esc($kpi['kode']) ?></code>
                &nbsp;·&nbsp; <?= esc($kpi['satuan']) ?>
                &nbsp;·&nbsp;
                <span style="color:<?= $kpi['polarity']==='max'
                    ? '#375623' : '#C00000' ?>">
                  <?= $kpi['polarity']==='max' ? '↑ Max' : '↓ Min' ?>
                  (<?= $kpi['perubahan_polarity']==='pos'
                      ? 'Positif' : 'Negatif' ?>)
                </span>
              </div>
            </div>

            <!-- MODIFIKASI: Input Target khusus untuk Admin -->
            <div style="width:115px">
              <div class="input-group input-group-sm">
                <span class="input-group-text bg-light text-muted" style="font-size:10px; padding: 2px 5px;">Trg</span>
                <input type="number"
                       name="target[]"
                       class="form-control text-center"
                       value="<?= esc($kpi['target'] ?? '100.00') ?>"
                       step="any" min="0"
                       placeholder="100" required>
              </div>
            </div>

            <!-- Input bobot -->
            <div style="width:120px">
              <div class="input-group input-group-sm">
                <input type="number"
                       name="bobot[]"
                       class="form-control bobot-input text-center"
                       value="<?= $kpi['bobot'] ?>"
                       step="0.001" min="0" max="1"
                       placeholder="0.10" required>
                <span class="input-group-text b-input-pct" style="font-size:11px; padding: 2px 6px;">
                  <?= round($kpi['bobot']*100, 1) ?>%
                </span>
              </div>
            </div>

            <!-- Hapus -->
            <a href="<?= base_url("kpi-pegawai/delete/{$kpi['id']}") ?>"
               class="btn btn-outline-danger btn-sm"
               style="padding:2px 7px"
               onclick="return confirm('Hapus KPI ini?')">
              <i class="ti ti-trash" style="font-size:13px"></i>
            </a>
          </div>
          <?php endforeach; ?>
          <?php endforeach; ?>

          <div class="p-3">
            <button type="submit" class="btn btn-primary btn-sm px-4">
              <i class="ti ti-device-floppy me-1"></i>
              Simpan Konfigurasi KPI
            </button>
          </div>
        </form>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- KOLOM KANAN: Pool KPI dari Unit Kerja -->
  <div class="col-md-5">
    <div class="card border-0 shadow-sm">
      <div class="card-header py-2" style="background:#EAF3DE">
        <span class="fw-semibold" style="color:#375623;font-size:13px">
          <i class="ti ti-plus me-1"></i>
          Tambah dari KPI Unit Kerja
        </span>
      </div>
      <div class="card-body p-0">
        <div class="p-2 border-bottom">
          <input type="text" id="search-kpi"
                 class="form-control form-control-sm"
                 placeholder="Cari nama KPI...">
        </div>
        <div style="max-height:480px;overflow-y:auto">
          <?php
          $persp_colors2 = [
              'Financial'        => ['#E6F1FB','#1F4E79'],
              'Customer'         => ['#EAF3DE','#375623'],
              'Internal Process' => ['#FFF3CD','#7F6000'],
              'Learning & Growth'=> ['#F3E5F5','#5C2A6B'],
          ];
          ?>
          <?php foreach ($poolGrouped as $perspektif => $kpis): ?>
          <?php $pc2 = $persp_colors2[$perspektif] ?? ['#f8f9fa','#333']; ?>
          <div class="px-3 py-1"
               style="background:<?= $pc2[0] ?>;
                      border-left:3px solid <?= $pc2[1] ?>">
            <small class="fw-semibold" style="color:<?= $pc2[1] ?>">
              <?= esc($perspektif) ?>
            </small>
          </div>
          <?php foreach ($kpis as $kpi): ?>
          <?php $isAssigned = in_array($kpi['kpi_id'], $assignedIds); ?>
          <div class="d-flex align-items-center gap-2 px-3 py-2
                      border-bottom kpi-pool-item"
               data-name="<?= strtolower($kpi['nama_kpi']) ?>">
            <div class="flex-grow-1">
              <div style="font-size:13px;
                <?= $isAssigned
                    ? 'color:#aaa;text-decoration:line-through' : '' ?>">
                <?= esc($kpi['nama_kpi']) ?>
              </div>
              <div style="font-size:11px;color:#888">
                <code><?= esc($kpi['kode']) ?></code>
                &nbsp;·&nbsp; <?= esc($kpi['satuan']) ?>
              </div>
            </div>
            <?php if ($isAssigned): ?>
              <span class="badge"
                    style="background:#C6EFCE;color:#375623;font-size:10px">
                ✓ Sudah
              </span>
            <?php else: ?>
              <form action="<?= base_url("kpi-pegawai/add/{$pegawai['id']}") ?>"
                    method="post" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="kpi_id"
                       value="<?= $kpi['kpi_id'] ?>">
                <input type="hidden" name="urutan"
                       value="<?= $kpi['urutan'] ?>">
                <button type="submit"
                        class="btn btn-outline-success btn-sm"
                        style="padding:2px 8px;font-size:11px">
                  <i class="ti ti-plus"></i> Tambah
                </button>
              </form>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Copy dari Pegawai Lain -->
<?php $grouped = $grouped ?? []; ?>
<div class="modal fade" id="modalCopy" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title fw-semibold">
          <i class="ti ti-copy me-1"></i> Copy KPI dari Pegawai Lain
        </h6>
        <button type="button" class="btn-close"
                data-bs-dismiss="modal"></button>
      </div>
      <form action="<?= base_url("kpi-pegawai/copy/{$pegawai['id']}") ?>"
            method="post">
        <?= csrf_field() ?>
        <div class="modal-body">
          <div class="alert alert-warning py-2 mb-3"
               style="font-size:12px">
            <i class="ti ti-alert-triangle me-1"></i>
            Fitur ini akan <strong>mengganti semua KPI</strong>
            pegawai ini dengan KPI dari pegawai yang dipilih.
          </div>
          <label class="form-label fw-semibold small">
            Pilih Pegawai Sumber
          </label>
          <select name="source_pegawai_id"
                  class="form-select form-select-sm" required>
            <option value="">-- Pilih pegawai --</option>
            <?php
            $allPegawaiFlat = [];
            foreach ($grouped as $divNama => $listPegawai) {
                foreach ($listPegawai as $p) {
                    if ($p['id'] != $pegawai['id']) {
                        $allPegawaiFlat[] = array_merge(
                            $p, ['nama_divisi_label' => $divNama]
                        );
                    }
                }
            }
            usort($allPegawaiFlat,
                fn($a,$b) => strcmp(
                    $a['nama_divisi_label'],
                    $b['nama_divisi_label']
                )
            );

            $currentDiv = '';
            foreach ($allPegawaiFlat as $p):
                if ($currentDiv !== $p['nama_divisi_label']):
                    if ($currentDiv !== '') echo '</optgroup>';
                    echo '<optgroup label="' . esc($p['nama_divisi_label']) . '">';
                    $currentDiv = $p['nama_divisi_label'];
                endif;
            ?>
            <option value="<?= $p['id'] ?>">
              <?= esc($p['nama']) ?>
              <?php if (!empty($p['jabatan'])): ?>
                (<?= esc($p['jabatan']) ?>)
              <?php endif; ?>
            </option>
            <?php endforeach; ?>
            <?php if ($currentDiv !== ''): ?>
              </optgroup>
            <?php endif; ?>
          </select>
          <div class="form-text mt-1" style="font-size:11px">
            Semua pegawai yang sudah memiliki KPI tersedia sebagai sumber.
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light btn-sm"
                  data-bs-dismiss="modal">Batal</button>
          <button type="submit"
                  class="btn btn-primary btn-sm"
                  onclick="return confirm('Yakin? KPI existing akan diganti!')">
            <i class="ti ti-copy me-1"></i> Copy KPI
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Search KPI
document.getElementById('search-kpi').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.kpi-pool-item').forEach(el => {
        el.style.display = el.dataset.name.includes(q) ? '' : 'none';
    });
});

// Hitung total bobot real-time & update badge persentase per baris
document.querySelectorAll('.bobot-input').forEach(input => {
    input.addEventListener('input', function() {
        const val = parseFloat(this.value) || 0;
        const pctSpan = this.closest('.input-group').querySelector('.b-input-pct');
        if (pctSpan) {
            pctSpan.textContent = (val * 100).toFixed(1) + '%';
        }
        hitungTotalBobot();
    });
});

function hitungTotalBobot() {
    let total = 0;
    document.querySelectorAll('.bobot-input').forEach(i => {
        total += parseFloat(i.value) || 0;
    });
    const pct = Math.round(total * 10000) / 100;
    document.getElementById('total-bobot-display').textContent = pct + '%';
    const alert = document.getElementById('alert-bobot');
    if (pct === 100) {
        alert.style.background   = '#E2EFDA';
        alert.style.borderColor  = '#70AD47';
    } else {
        alert.style.background   = '#FCE4D6';
        alert.style.borderColor  = '#C00000';
    }
}
</script>
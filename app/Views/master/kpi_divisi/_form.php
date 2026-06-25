<div class="d-flex align-items-center gap-2 mb-3">
  <a href="<?= base_url('master/kpi-divisi') ?>"
     class="btn btn-sm btn-light border">
    <i class="ti ti-arrow-left"></i>
  </a>
  <div>
    <h5 class="mb-0 fw-semibold" style="color:#1F4E79">
      KPI Divisi — <?= esc($divisi['nama']) ?>
    </h5>
    <small class="text-muted">
      Assign KPI dari Master KPI dan set bobot khusus divisi ini
    </small>
  </div>
</div>

<!-- Total bobot indicator -->
<div class="alert py-2 mb-3 d-flex align-items-center gap-2"
     id="alert-bobot"
     style="background:<?= round($totalBobot*100,2)==100 ? '#E2EFDA' : '#FCE4D6' ?>;
            border:1px solid <?= round($totalBobot*100,2)==100 ? '#70AD47' : '#C00000' ?>">
  <i class="ti ti-calculator"></i>
  <span style="font-size:13px">
    Total bobot saat ini:
    <strong id="total-bobot-display">
      <?= round($totalBobot * 100, 2) ?>%
    </strong>
    <?= round($totalBobot*100,2)==100
        ? '— ✓ Sudah tepat 100%'
        : '— Harus tepat 100%!' ?>
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
          KPI Divisi <?= esc($divisi['nama']) ?>
          <span class="badge bg-primary ms-1"><?= count($assigned) ?></span>
        </span>
      </div>
      <div class="card-body p-0">
        <?php if (empty($assigned)): ?>
          <div class="text-center py-4 text-muted" style="font-size:13px">
            <i class="ti ti-playlist-x fs-2 d-block mb-1"></i>
            Belum ada KPI. Pilih dari daftar kanan.
          </div>
        <?php else: ?>

        <form action="<?= base_url("master/kpi-divisi/store/{$divisi['id']}") ?>"
              method="post" id="form-kpi-divisi">
          <?= csrf_field() ?>

          <?php
          $persp_colors = [
              'Financial'        => ['bg'=>'#E6F1FB','border'=>'#2E75B6','text'=>'#1F4E79'],
              'Customer'         => ['bg'=>'#EAF3DE','border'=>'#70AD47','text'=>'#375623'],
              'Internal Process' => ['bg'=>'#FFF3CD','border'=>'#BF9000','text'=>'#7F6000'],
              'Learning & Growth'=> ['bg'=>'#F3E5F5','border'=>'#9B59B6','text'=>'#5C2A6B'],
          ];
          ?>

          <?php foreach ($assignedGrouped as $perspektif => $kpis): ?>
          <?php $c = $persp_colors[$perspektif] ?? ['bg'=>'#f8f9fa','border'=>'#dee2e6','text'=>'#333']; ?>
          <div class="px-3 py-1"
               style="background:<?= $c['bg'] ?>;border-left:3px solid <?= $c['border'] ?>">
            <small class="fw-semibold" style="color:<?= $c['text'] ?>">
              <?= esc($perspektif) ?>
            </small>
          </div>
          <?php foreach ($kpis as $i => $kpi): ?>
          <div class="d-flex align-items-center gap-2 px-3 py-2
                      border-bottom kpi-assigned-row"
               data-id="<?= $kpi['id'] ?>">
            <input type="hidden" name="kpi_id[]" value="<?= $kpi['kpi_id'] ?>">
            <input type="hidden" name="urutan[]" value="<?= $kpi['urutan'] ?>">

            <div class="flex-grow-1">
              <div class="fw-semibold" style="font-size:13px">
                <?= esc($kpi['nama_kpi']) ?>
              </div>
              <div style="font-size:11px;color:#888">
                <code><?= esc($kpi['kode']) ?></code>
                &nbsp;·&nbsp; <?= esc($kpi['satuan']) ?>
                &nbsp;·&nbsp;
                <span style="color:<?= $kpi['polarity']==='max' ? '#375623' : '#C00000' ?>">
                  <?= $kpi['polarity']==='max' ? '↑ Max' : '↓ Min' ?>
                  (<?= $kpi['perubahan_polarity'] === 'pos' ? 'Positif' : 'Negatif' ?>)
                </span>
              </div>
            </div>

            <div style="width:110px">
              <div class="input-group input-group-sm">
                <input type="number"
                       name="bobot[]"
                       class="form-control bobot-input"
                       value="<?= $kpi['bobot'] ?>"
                       step="0.001" min="0" max="1"
                       placeholder="0.10">
                <span class="input-group-text" style="font-size:11px">
                  (<?= round($kpi['bobot']*100,1) ?>%)
                </span>
              </div>
            </div>

            <a href="<?= base_url("master/kpi-divisi/delete/{$kpi['id']}") ?>"
               class="btn btn-outline-danger btn-sm"
               style="padding:2px 7px"
               onclick="return confirm('Hapus KPI ini dari divisi?')">
              <i class="ti ti-trash" style="font-size:13px"></i>
            </a>
          </div>
          <?php endforeach; ?>
          <?php endforeach; ?>

          <div class="p-3">
            <button type="submit" class="btn btn-primary btn-sm px-4"
                    id="btn-simpan">
              <i class="ti ti-device-floppy me-1"></i>
              Simpan Perubahan Bobot
            </button>
          </div>
        </form>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- KOLOM KANAN: Pilih KPI dari Direktorat -->
  <div class="col-md-5">
    <div class="card border-0 shadow-sm">
        <div class="card-header py-2"
            style="background:#EAF3DE">
        <span class="fw-semibold" style="color:#375623;font-size:13px">
            <i class="ti ti-plus me-1"></i>
            Tambah dari KPI <?= esc($direktorat['nama'] ?? 'Direktorat') ?>
        </span>
        </div>
        <div class="card-body p-0">
        <!-- Search -->
        <div class="p-2 border-bottom">
            <input type="text" id="search-kpi"
                class="form-control form-control-sm"
                placeholder="Cari nama KPI...">
        </div>

        <?php if (empty($poolGrouped)): ?>
            <div class="text-center py-4 text-muted" style="font-size:13px">
            <i class="ti ti-alert-circle d-block fs-2 mb-1"></i>
            Belum ada KPI Unit untuk direktorat ini.
            <br>
            <a href="<?= base_url("master/kpi-unit/{$direktorat['id']}") ?>"
                class="btn btn-sm btn-outline-primary mt-2"
                style="font-size:12px">
                Setup KPI Unit Direktorat
            </a>
            </div>
        <?php else: ?>
        <div style="max-height:420px;overflow-y:auto">
            <?php
            $persp_colors = [
                'Financial'        => ['bg'=>'#E6F1FB','border'=>'#2E75B6','text'=>'#1F4E79'],
                'Customer'         => ['bg'=>'#EAF3DE','border'=>'#70AD47','text'=>'#375623'],
                'Internal Process' => ['bg'=>'#FFF3CD','border'=>'#BF9000','text'=>'#7F6000'],
                'Learning & Growth'=> ['bg'=>'#F3E5F5','border'=>'#9B59B6','text'=>'#5C2A6B'],
            ];
            ?>
            <?php foreach ($poolGrouped as $perspektif => $kpis): ?>
            <?php $c = $persp_colors[$perspektif] ?? ['bg'=>'#f8f9fa','border'=>'#dee2e6','text'=>'#333']; ?>
            <div class="px-3 py-1"
                style="background:<?= $c['bg'] ?>;border-left:3px solid <?= $c['border'] ?>">
            <small class="fw-semibold" style="color:<?= $c['text'] ?>">
                <?= esc($perspektif) ?>
            </small>
            </div>
            <?php foreach ($kpis as $kpi): ?>
            <?php $isAssigned = in_array($kpi['id'], $assignedIds); ?>
            <div class="d-flex align-items-center gap-2 px-3 py-2
                        border-bottom kpi-master-item"
                data-name="<?= strtolower($kpi['nama_kpi']) ?>">
            <div class="flex-grow-1">
                <div style="font-size:13px;
                <?= $isAssigned ? 'color:#aaa;text-decoration:line-through' : '' ?>">
                <?= esc($kpi['nama_kpi']) ?>
                </div>
                <div style="font-size:11px;color:#888">
                <code><?= esc($kpi['kode']) ?></code>
                &nbsp;·&nbsp; <?= esc($kpi['satuan']) ?>
                &nbsp;·&nbsp;
                <span style="color:<?= $kpi['polarity']==='max'?'#375623':'#C00000' ?>">
                    <?= $kpi['polarity']==='max' ? '↑' : '↓' ?>
                </span>
                </div>
            </div>
            <?php if ($isAssigned): ?>
                <span class="badge"
                    style="background:#C6EFCE;color:#375623;font-size:10px">
                ✓ Sudah
                </span>
            <?php else: ?>
                <form action="<?= base_url("master/kpi-divisi/add/{$divisi['id']}") ?>"
                    method="post" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="kpi_id"  value="<?= $kpi['id'] ?>">
                <input type="hidden" name="bobot"   value="0.05">
                <input type="hidden" name="urutan"  value="<?= $kpi['urutan'] ?>">
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
        <?php endif; ?>
        </div>
    </div>
  </div>
</div>

<script>
// Search KPI
document.getElementById('search-kpi').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.kpi-master-item').forEach(el => {
        el.style.display = el.dataset.name.includes(q) ? '' : 'none';
    });
});

// Hitung total bobot real-time
document.querySelectorAll('.bobot-input').forEach(input => {
    input.addEventListener('input', hitungTotalBobot);
});

function hitungTotalBobot() {
    let total = 0;
    document.querySelectorAll('.bobot-input').forEach(input => {
        total += parseFloat(input.value) || 0;
    });
    const pct = Math.round(total * 10000) / 100;
    document.getElementById('total-bobot-display').textContent = pct + '%';
    const alert = document.getElementById('alert-bobot');
    if (pct === 100) {
        alert.style.background = '#E2EFDA';
        alert.style.borderColor = '#70AD47';
    } else {
        alert.style.background = '#FCE4D6';
        alert.style.borderColor = '#C00000';
    }
}
</script>
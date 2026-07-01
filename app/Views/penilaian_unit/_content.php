<div class="d-flex align-items-center gap-2 mb-3">
  <a href="<?= base_url('penilaian-unit') ?>"
     class="btn btn-sm btn-light border">
    <i class="ti ti-arrow-left"></i>
  </a>
  <div>
    <h5 class="mb-0 fw-semibold" style="color:#1F4E79">
      KPI Unit — <?= esc($divisi['nama']) ?>
    </h5>
    <small class="text-muted">
      Periode: <strong><?= esc($periodeAktif['nama']) ?></strong>
      &nbsp;·&nbsp; Nilai ini berlaku untuk semua pegawai di divisi ini (bobot 30%)
    </small>
  </div>
</div>

<!-- Summary cards -->
<div class="row g-3 mb-3">
  <div class="col-md-3">
    <div class="stat-card text-center">
      <div class="stat-value" id="display-nilai" style="color:#2E75B6">
        <?= $nilai > 0 ? number_format($nilai, 2).'%' : '—' ?>
      </div>
      <div class="stat-label">Rata-rata Capaian</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="stat-card text-center">
      <?php
      $grade_map = [
          'A'=>['#C6EFCE','#375623'],
          'B'=>['#BDD7EE','#1F4E79'],
          'C'=>['#FFF2CC','#7F6000'],
          'D'=>['#FCE4D6','#C00000'],
          'E'=>['#FFCCCC','#7B0000'],
      ];
      $gm = $grade_map[$grade ?? ''] ?? ['#f0f0f0','#888'];
      ?>
      <div class="stat-value" id="display-grade"
           style="color:<?= $gm[1] ?>;background:<?= $gm[0] ?>;
                  border-radius:8px;padding:4px 12px;display:inline-block">
        <?= $grade ?? '—' ?>
      </div>
      <div class="stat-label mt-1">Grade Unit</div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="stat-card">
      <div class="mb-1" style="font-size:12px;color:#6B7280">
        Progress Pengisian KPI Unit
      </div>
      <?php $pct = $totalKpi > 0 ? round(count($existing)/$totalKpi*100) : 0; ?>
      <div class="progress" style="height:8px">
        <div class="progress-bar bg-primary" style="width:<?= $pct ?>%"></div>
      </div>
      <div style="font-size:11px;color:#6B7280;margin-top:4px">
        <?= count($existing) ?> dari <?= $totalKpi ?> KPI sudah diisi
      </div>
    </div>
  </div>
</div>

<!-- Form -->
<form action="<?= base_url("penilaian-unit/store/{$divisi['id']}") ?>"
      method="post" id="form-kpi-unit">
  <?= csrf_field() ?>

  <?php
  $persp_colors = [
      'Financial'        => ['bg'=>'#E6F1FB','border'=>'#2E75B6','text'=>'#1F4E79'],
      'Customer'         => ['bg'=>'#EAF3DE','border'=>'#70AD47','text'=>'#375623'],
      'Internal Process' => ['bg'=>'#FFF3CD','border'=>'#BF9000','text'=>'#7F6000'],
      'Learning & Growth'=> ['bg'=>'#F3E5F5','border'=>'#9B59B6','text'=>'#5C2A6B'],
  ];
  ?>

  <?php foreach ($kpiGrouped as $perspektif => $kpis): ?>
  <?php $c = $persp_colors[$perspektif] ?? ['bg'=>'#f8f9fa','border'=>'#dee2e6','text'=>'#333']; ?>
  <div class="card mb-3 border-0 shadow-sm">
    <div class="card-header py-2 d-flex align-items-center
                justify-content-between"
         style="background:<?= $c['bg'] ?>;border-left:4px solid <?= $c['border'] ?>">
      <span class="fw-semibold" style="color:<?= $c['text'] ?>;font-size:13px">
        <?= esc($perspektif) ?>
      </span>
      <?php $bobot_persp = array_sum(array_column($kpis,'bobot')); ?>
      <span class="badge" style="background:<?= $c['border'] ?>;font-size:11px">
        Bobot: <?= round($bobot_persp*100,1) ?>%
      </span>
    </div>
    <div class="card-body p-0">
      <table class="table table-sm align-middle mb-0" style="font-size:13px">
        <thead style="background:#f8fafc">
          <tr>
            <th style="width:30px"></th>
            <th>KPI Unit</th>
            <th style="width:60px" class="text-center">Bobot</th>
            <th style="width:60px" class="text-center">Polarity</th>
            <th style="width:130px">Target</th>
            <th style="width:130px">Realisasi</th>
            <th style="width:100px" class="text-center">Capaian %</th>
            <th style="width:150px">Catatan</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($kpis as $kpi): ?>
          <?php
          $ex  = $existing[$kpi['id']] ?? null;
          $pol_color = $kpi['polarity']==='max' ? '#375623' : '#C00000';
          ?>
          <tr>
            <td class="text-center">
              <code style="font-size:10px;color:#888">
                <?= esc($kpi['kode']) ?>
              </code>
            </td>
            <td>
              <span class="fw-semibold"><?= esc($kpi['nama_kpi']) ?></span>
              <small class="text-muted d-block" style="font-size:11px">
                Satuan: <?= esc($kpi['satuan']) ?>
              </small>
            </td>
            <td class="text-center fw-semibold" style="color:#1F4E79">
              <?= round($kpi['bobot']*100,2) ?>%
            </td>
            <td class="text-center">
              <span style="font-size:13px;font-weight:600;
                           color:<?= $pol_color ?>">
                <?= $kpi['polarity']==='max' ? '↑' : '↓' ?>
              </span>
            </td>
            <td>
              <input type="number"
                     name="target[<?= $kpi['id'] ?>]"
                     id="target_<?= $kpi['id'] ?>"
                     class="form-control form-control-sm kpi-unit-input"
                     data-kpi="<?= $kpi['id'] ?>"
                     value="<?= $ex['target'] ?? '' ?>"
                     step="any" placeholder="0">
            </td>
            <td>
              <input type="number"
                     name="realisasi[<?= $kpi['id'] ?>]"
                     id="realisasi_<?= $kpi['id'] ?>"
                     class="form-control form-control-sm kpi-unit-input"
                     data-kpi="<?= $kpi['id'] ?>"
                     value="<?= $ex['realisasi'] ?? '' ?>"
                     step="any" placeholder="0">
            </td>
            <td class="text-center">
              <?php
              if ($ex) {
                  $cap = $ex['capaian'];
                  if ($cap >= 1)     { $cbg='#C6EFCE'; $cc='#375623'; }
                  elseif ($cap>=0.76){ $cbg='#BDD7EE'; $cc='#1F4E79'; }
                  elseif ($cap>=0.61){ $cbg='#FFF2CC'; $cc='#7F6000'; }
                  else               { $cbg='#FCE4D6'; $cc='#C00000'; }
              } else { $cbg='#f0f0f0'; $cc='#888'; }
              ?>
              <span id="capaian_<?= $kpi['id'] ?>"
                    class="badge"
                    style="font-size:12px;min-width:60px;
                           background:<?= $cbg ?>;color:<?= $cc ?>">
                <?= $ex ? round($ex['capaian']*100,2).'%' : '—' ?>
              </span>
            </td>
            <td>
              <input type="text"
                     name="catatan[<?= $kpi['id'] ?>]"
                     class="form-control form-control-sm"
                     value="<?= esc($ex['catatan'] ?? '') ?>"
                     placeholder="Opsional">
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endforeach; ?>

  <div class="d-flex gap-2 mt-2 mb-4">
    <button type="submit" class="btn btn-primary px-4">
      <i class="ti ti-device-floppy me-1"></i> Simpan KPI Unit
    </button>
    <a href="<?= base_url('penilaian-unit') ?>"
       class="btn btn-light border px-4">Batal</a>
  </div>
</form>

<script>
const BASE_URL = '<?= base_url() ?>';

// Token CSRF disimpan sebagai variabel yang dapat diperbarui — lihat
// catatan yang sama di penilaian_unit/_form.php: Config\Security
// ::$regenerate = true membuat token berubah setelah setiap verifikasi
// berhasil, sehingga nilai statis akan membuat permintaan kedua dst.
// pada halaman ini (yang berisi banyak baris KPI) ditolak server (403).
let csrfTokenName = '<?= csrf_token() ?>';
let csrfHashValue = '<?= csrf_hash() ?>';

document.querySelectorAll('.kpi-unit-input').forEach(input => {
    input.addEventListener('input', function() {
        const kpiId     = this.dataset.kpi;
        const target    = document.getElementById('target_'+kpiId)?.value    || 0;
        const realisasi = document.getElementById('realisasi_'+kpiId)?.value || 0;
        if (target > 0 && realisasi > 0) hitungCapaian(kpiId, target, realisasi);
    });
});

function hitungCapaian(kpiId, target, realisasi) {
    const fd = new FormData();
    fd.append('kpi_id',    kpiId);
    fd.append('target',    target);
    fd.append('realisasi', realisasi);
    fd.append(csrfTokenName, csrfHashValue);

    fetch(BASE_URL + 'penilaian-unit/ajax-hitung', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: fd
    })
    .then(r => r.json())
    .then(data => {
        if (data.csrf_hash) {
            csrfHashValue = data.csrf_hash;
            document.querySelectorAll('input[type="hidden"][name="' + csrfTokenName + '"]')
                    .forEach(function(el) { el.value = data.csrf_hash; });
        }

        const el = document.getElementById('capaian_' + kpiId);
        if (!el) return;
        el.textContent = data.pct;
        const colors = {
            success: {bg:'#C6EFCE',color:'#375623'},
            primary: {bg:'#BDD7EE',color:'#1F4E79'},
            warning: {bg:'#FFF2CC',color:'#7F6000'},
            danger:  {bg:'#FCE4D6',color:'#C00000'},
        };
        const c = colors[data.color] || colors.danger;
        el.style.background = c.bg;
        el.style.color      = c.color;
    });
}
</script>
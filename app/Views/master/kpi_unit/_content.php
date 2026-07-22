<div class="d-flex align-items-center gap-2 mb-3">
  <a href="<?= base_url('master/direktorat') ?>"
     class="btn btn-sm btn-light border">
    <i class="ti ti-arrow-left"></i>
  </a>
  <div class="flex-grow-1">
    <h5 class="mb-0 fw-semibold" style="color:#1F4E79">
      KPI Unit — <?= esc($direktorat['nama']) ?>
    </h5>
    <small class="text-muted">
      <code><?= esc($direktorat['kode']) ?></code>
    </small>
  </div>
  <a href="<?= base_url("master/kpi-unit/{$direktorat['id']}/create") ?>"
     class="btn btn-primary btn-sm">
    <i class="ti ti-plus me-1"></i> Tambah KPI
  </a>
  <a href="<?= base_url("master/kpi-unit/{$direktorat['id']}/import") ?>"
     class="btn btn-outline-secondary btn-sm">
    <i class="ti ti-file-import me-1"></i> Import Excel
  </a>
</div>

<!-- Fitur Pencarian KPI Unit -->
<div class="mb-3 d-flex align-items-center gap-3 flex-wrap">
  <div class="input-group input-group-sm" style="max-width:340px">
    <span class="input-group-text bg-light"><i class="ti ti-search text-muted"></i></span>
    <input type="text" id="cari-kpi-unit" class="form-control"
           placeholder="Cari nama KPI atau kode..." autocomplete="off">
    <button type="button" class="btn btn-light border" id="reset-cari-kpi"
            title="Reset" style="display:none">
      <i class="ti ti-x" style="font-size:12px"></i>
    </button>
  </div>
  <small class="text-muted" id="info-cari-kpi" style="font-size:11px"></small>
</div>

<!-- Total bobot -->


<?php
$persp_colors = [
    'Financial'        => ['bg'=>'#E6F1FB','border'=>'#2E75B6','text'=>'#1F4E79'],
    'Customer'         => ['bg'=>'#EAF3DE','border'=>'#70AD47','text'=>'#375623'],
    'Internal Process' => ['bg'=>'#FFF3CD','border'=>'#BF9000','text'=>'#7F6000'],
    'Learning & Growth'=> ['bg'=>'#F3E5F5','border'=>'#9B59B6','text'=>'#5C2A6B'],
];
?>

<?php foreach ($grouped as $perspektif => $kpis): ?>
<?php $c = $persp_colors[$perspektif] ?? ['bg'=>'#f8f9fa','border'=>'#dee2e6','text'=>'#333']; ?>
<div class="card mb-3 border-0 shadow-sm">
  <div class="card-header py-2 d-flex align-items-center justify-content-between"
       style="background:<?= $c['bg'] ?>;border-left:4px solid <?= $c['border'] ?>">
    <span class="fw-semibold" style="color:<?= $c['text'] ?>;font-size:13px">
      <?= esc($perspektif) ?>
    </span>
    <!-- <?php $bobot_persp = array_sum(array_column($kpis, 'bobot')); ?>
    <span class="badge" style="background:<?= $c['border'] ?>;font-size:11px">
      Bobot: <?= round($bobot_persp * 100, 2) ?>%
    </span> -->
  </div>
  <div class="card-body p-0">
    <table class="table table-sm table-hover align-middle mb-0"
           style="font-size:13px">
      <thead style="background:#f8fafc">
        <tr>
          <th style="width:30px">No</th>
          <th>Nama KPI</th>
          <th style="width:90px">Kode</th>
          <th style="width:70px">Satuan</th>
          <!-- <th style="width:80px" class="text-center">Bobot</th> -->
          <th style="width:90px" class="text-center">Polarity</th>
          <th style="width:80px" class="text-center">Perubahan</th>
          <th style="width:90px" class="text-center">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($kpis as $kpi): ?>
        <tr class="kpi-unit-row" data-search="<?= esc(strtolower($kpi['nama_kpi'] . ' ' . $kpi['kode'] . ' ' . $kpi['satuan'])) ?>">
          <td class="text-muted"><?= $kpi['urutan'] ?></td>
          <td class="fw-semibold"><?= esc($kpi['nama_kpi']) ?></td>
          <td>
            <code style="font-size:11px;background:#f0f4ff;
                         padding:2px 5px;border-radius:4px;color:#2E75B6">
              <?= esc($kpi['kode']) ?>
            </code>
          </td>
          <td><?= esc($kpi['satuan']) ?></td>
          <!-- <td class="text-center fw-semibold" style="color:#1F4E79">
            <?= round($kpi['bobot'] * 100, 2) ?>%
          </td> -->
          <?php
            $polarityBadges = [
                'max'        => ['↑ Max', '#E2EFDA', '#375623'],
                'min'        => ['↓ Min', '#FCE4D6', '#C00000'],
                'precise'    => ['◎ Precise is Better', '#DDEBF7', '#1F4E79'],
                'special'    => ['⚑ Special Scoring', '#FFF2CC', '#7F6000'],
                'tertimbang' => ['⚖ Scoring Tertimbang', '#E4DFEC', '#5C2A6B'],
            ];
            [$pLabel, $pBg, $pColor] = $polarityBadges[$kpi['polarity']] ?? ['—', '#f0f0f0', '#888'];
          ?>
          <td class="text-center">
            <span class="badge" style="background:<?= $pBg ?>;color:<?= $pColor ?>;font-size:11px">
              <?= $pLabel ?>
            </span>
          </td>
          <td class="text-center">
            <?php if (in_array($kpi['polarity'], ['max', 'min'], true)): ?>
              <span class="badge"
                style="background:<?= $kpi['perubahan_polarity']==='pos'?'#E2EFDA':'#FCE4D6' ?>;
                       color:<?= $kpi['perubahan_polarity']==='pos'?'#375623':'#C00000' ?>;
                       font-size:11px">
                <?= $kpi['perubahan_polarity']==='pos' ? 'Positif' : 'Negatif' ?>
              </span>
            <?php elseif ($kpi['polarity'] === 'precise'): ?>
              <span class="text-muted" style="font-size:10px">
                ±<?= esc($kpi['toleransi_skor4'] ?? '-') ?>/±<?= esc($kpi['toleransi_skor3'] ?? '-') ?>/±<?= esc($kpi['toleransi_skor2'] ?? '-') ?>%
              </span>
            <?php elseif ($kpi['polarity'] === 'special'): ?>
              <span class="text-muted" style="font-size:10px">
                Sifat: <?= $kpi['sifat_khusus'] === 'minimize' ? 'Minimize' : 'Maximize' ?>
              </span>
            <?php elseif ($kpi['polarity'] === 'tertimbang'): ?>
              <span class="text-muted" style="font-size:10px">Skor × Pengkali Harian</span>
            <?php else: ?>
              <span class="text-muted" style="font-size:10px">—</span>
            <?php endif; ?>
          </td>
          <td class="text-center">
            <div class="d-flex gap-1 justify-content-center">
              <a href="<?= base_url("master/kpi-unit/edit/{$kpi['id']}") ?>"
                 class="btn btn-outline-primary"
                 style="padding:2px 8px;font-size:11px">
                <i class="ti ti-edit"></i>
              </a>
              <a href="<?= base_url("master/kpi-unit/delete/{$kpi['id']}") ?>"
                 class="btn btn-outline-danger"
                 style="padding:2px 8px;font-size:11px"
                 onclick="return confirmAction(event, { title: 'Hapus KPI', text: 'Hapus KPI ini?', confirmText: 'Ya, Hapus', danger: true })">
                <i class="ti ti-trash"></i>
              </a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endforeach; ?>

<?php if (empty($grouped)): ?>
  <div class="text-center py-5 text-muted">
    <i class="ti ti-playlist-x fs-1 d-block mb-2"></i>
    Belum ada KPI Unit untuk direktorat ini.
  </div>
<?php endif; ?>
<script>
(function () {
    const input   = document.getElementById('cari-kpi-unit');
    const resetBtn= document.getElementById('reset-cari-kpi');
    const info    = document.getElementById('info-cari-kpi');
    const rows    = document.querySelectorAll('.kpi-unit-row');

    function filter() {
        const q = input.value.toLowerCase().trim();
        resetBtn.style.display = q ? 'block' : 'none';
        let tampil = 0;
        rows.forEach(r => {
            const match = !q || r.dataset.search.includes(q);
            r.style.display = match ? '' : 'none';
            if (match) tampil++;
        });
        info.textContent = q
            ? `${tampil} dari ${rows.length} KPI ditemukan`
            : '';
    }

    input.addEventListener('input', filter);
    resetBtn.addEventListener('click', function () {
        input.value = '';
        filter();
        input.focus();
    });
})();
</script>
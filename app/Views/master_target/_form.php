<div class="d-flex align-items-center gap-2 mb-3">
  <a href="<?= base_url('master-target') ?>" class="btn btn-sm btn-light border">
    <i class="ti ti-arrow-left"></i>
  </a>
  <div class="flex-grow-1">
    <h5 class="mb-0 fw-semibold" style="color:#1F4E79">
      Master Target — <?= esc($pegawai['nama']) ?>
    </h5>
    <small class="text-muted"><?= esc($pegawai['jabatan'] ?? '') ?></small>
  </div>
  <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalCopyMasterTarget">
    <i class="ti ti-copy me-1"></i> Copy Target dari Pegawai Lain
  </button>
  <a href="<?= base_url("master-target/import?pegawai_id={$pegawai['id']}&tahun={$tahun}") ?>" class="btn btn-sm btn-outline-primary">
    <i class="ti ti-file-import me-1"></i> Import Excel
  </a>
</div>

<!-- Modal Copy Target dari Pegawai Lain -->
<div class="modal fade" id="modalCopyMasterTarget" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title fw-semibold"><i class="ti ti-copy me-1"></i> Copy Target dari Pegawai Lain</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="<?= base_url("master-target/copy/{$pegawai['id']}") ?>" method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="tahun" value="<?= $tahun ?>">
        <div class="modal-body">
          <div class="alert py-2 mb-3" style="font-size:12px;background:#FFF3CD;border:1px solid #BF9000;color:#7F6000">
            Hanya mengisi Target/Bobot yang <strong>masih kosong</strong> untuk Tahun <?= $tahun ?> — data yang sudah terisi tidak akan ditimpa.
            Parameter dicocokkan berdasarkan KPI &amp; Nama Turunan yang sama antara kedua pegawai.
          </div>
          <label class="form-label fw-semibold small">Pilih Pegawai Sumber</label>
          <?php if (empty($groupedPegawaiSumber)): ?>
            <div class="text-muted" style="font-size:12px">Belum ada pegawai lain yang memiliki KPI.</div>
          <?php else: ?>
          <select name="source_pegawai_id" class="form-select form-select-sm" required>
            <option value="">-- Pilih pegawai --</option>
            <?php foreach ($groupedPegawaiSumber as $divNama => $listPegawai): ?>
              <optgroup label="<?= esc($divNama) ?>">
                <?php foreach ($listPegawai as $p): ?>
                  <option value="<?= $p['id'] ?>">
                    <?= esc($p['nama']) ?><?= !empty($p['jabatan']) ? ' (' . esc($p['jabatan']) . ')' : '' ?>
                  </option>
                <?php endforeach; ?>
              </optgroup>
            <?php endforeach; ?>
          </select>
          <?php endif; ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary btn-sm" <?= empty($groupedPegawaiSumber) ? 'disabled' : '' ?>>
            <i class="ti ti-copy me-1"></i> Salin
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="alert py-2 mb-3 d-flex align-items-center gap-2" style="background:#E6F1FB;border:1px solid #2E75B6;font-size:12px">
  <i class="ti ti-info-circle" style="color:#1F4E79"></i>
  <span style="color:#1F4E79">
    Isi Target untuk seluruh 12 bulan dan Bobot untuk tahun ini. Target Periode Bulanan/Triwulan/Semester/Tahunan
    akan dihitung otomatis (Bulanan = bulan itu langsung, Triwulan/Semester/Tahunan = rata-rata bulan-bulan terkait).
    Total Bobot seluruh KPI harus tepat 100%.
  </span>
</div>

<div class="card border-0 shadow-sm mb-3">
  <div class="card-body py-2">
    <form method="get" action="<?= base_url("master-target/edit/{$pegawai['id']}") ?>" id="form-pilih-tahun" class="d-flex align-items-center gap-2">
      <label class="form-label small fw-semibold mb-0">Tahun</label>
      <select name="tahun" class="form-select form-select-sm" style="width:120px" onchange="document.getElementById('form-pilih-tahun').submit()">
        <?php for ($y = (int)date('Y') - 2; $y <= (int)date('Y') + 3; $y++): ?>
          <option value="<?= $y ?>" <?= $tahun == $y ? 'selected' : '' ?>><?= $y ?></option>
        <?php endfor; ?>
      </select>
    </form>
  </div>
</div>

<?php
$totalBobotAwal = 0.0;
foreach ($assignedGrouped as $kpis) {
    foreach ($kpis as $kpi) {
        $listT = $turunanByInduk[$kpi['id']] ?? [];
        if (empty($listT)) {
            $totalBobotAwal += (float)($bobotIndexed[$kpi['id']] ?? 0);
        } else {
            foreach ($listT as $t) {
                $totalBobotAwal += (float)($turunanBobotIndexed[$t['id']] ?? 0);
            }
        }
    }
}
$bobotPct = round($totalBobotAwal * 100, 2);
?>
<div class="alert py-2 mb-3 d-flex align-items-center gap-2" id="alert-bobot"
     style="background:<?= $bobotPct==100?'#E2EFDA':'#FCE4D6' ?>;border:1px solid <?= $bobotPct==100?'#70AD47':'#C00000' ?>">
  <i class="ti ti-calculator"></i>
  <span style="font-size:13px">
    Total Bobot: <strong id="total-bobot-display"><?= $bobotPct ?>%</strong>
    <?= $bobotPct==100 ? '— ✓ Sudah tepat 100%' : '— Harus tepat 100%!' ?>
  </span>
</div>

<form action="<?= base_url("master-target/save/{$pegawai['id']}") ?>" method="post">
  <?= csrf_field() ?>
  <input type="hidden" name="tahun" value="<?= $tahun ?>">

  <?php
  $persp_colors = [
      'Financial'         => ['bg'=>'#E6F1FB','border'=>'#2E75B6','text'=>'#1F4E79'],
      'Customer'          => ['bg'=>'#EAF3DE','border'=>'#70AD47','text'=>'#375623'],
      'Internal Process'  => ['bg'=>'#FFF3CD','border'=>'#BF9000','text'=>'#7F6000'],
      'Learning & Growth' => ['bg'=>'#F3E5F5','border'=>'#9B59B6','text'=>'#5C2A6B'],
  ];
  $polarityLabels = [
      'max' => '↑ Max', 'min' => '↓ Min', 'precise' => '◎ Precise',
      'special' => '⚑ Special', 'tertimbang' => '⚖ Tertimbang',
  ];
  $namaBulan = [1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'Mei',6=>'Jun',7=>'Jul',8=>'Agu',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Des'];
  $fmtNum = function ($v) {
      if ($v === null || $v === '') return '';
      return rtrim(rtrim(sprintf('%.4f', (float)$v), '0'), '.');
  };
  ?>

  <?php foreach ($assignedGrouped as $perspektif => $kpis): ?>
  <?php $ps = $persp_colors[$perspektif] ?? ['bg'=>'#f8f9fa','border'=>'#dee2e6','text'=>'#333']; ?>
  <div class="card mb-3 border-0 shadow-sm">
    <div class="card-header py-2" style="background:<?= $ps['bg'] ?>;border-left:4px solid <?= $ps['border'] ?>">
      <span class="fw-semibold" style="color:<?= $ps['text'] ?>;font-size:13px"><?= esc($perspektif) ?></span>
    </div>
    <div class="card-body p-0" style="overflow-x:auto">
      <?php foreach ($kpis as $kpi): ?>
      <?php
        $listTurunan = $turunanByInduk[$kpi['id']] ?? [];
        $punyaTurunan = !empty($listTurunan);
        $isSpecial   = $kpi['polarity'] === 'special';
        $bulanIndukData = $targetBulananByInduk[$kpi['id']] ?? [];
        $bobotIndukVal  = $bobotIndexed[$kpi['id']] ?? null;
      ?>
      <div class="border-bottom px-3 py-2">
        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
          <span class="fw-semibold" style="font-size:13px"><?= esc($kpi['nama_kpi']) ?></span>
          <span class="text-muted" style="font-size:11px"><code><?= esc($kpi['kode']) ?></code> <?= esc($kpi['satuan'] ?? '') ?></span>
          <span style="font-size:11px"><?= $polarityLabels[$kpi['polarity']] ?? '—' ?></span>
          <?php if ($punyaTurunan): ?>
            <span class="badge bg-light text-dark border" style="font-size:10px"><?= count($listTurunan) ?> Parameter Turunan</span>
          <?php endif; ?>
        </div>

        <?php if (!$punyaTurunan): ?>
        <div class="d-flex align-items-center gap-1 flex-wrap">
          <?php if (!$isSpecial): foreach ($namaBulan as $b => $label): ?>
            <div style="width:70px">
              <label class="form-label mb-0" style="font-size:10px;color:#888"><?= $label ?></label>
              <input type="number" id="target-<?= $kpi['id'] ?>-<?= $b ?>" name="target[<?= $kpi['id'] ?>][<?= $b ?>]" class="form-control form-control-sm text-center"
                     style="font-size:11px;padding:2px 4px" step="any" min="0"
                     value="<?= old("target.{$kpi['id']}.{$b}", $fmtNum($bulanIndukData[$b] ?? '')) ?>" placeholder="0">
            </div>
          <?php endforeach; else: ?>
            <span class="text-muted" style="font-size:11px">Target tidak berlaku untuk Special Scoring</span>
          <?php endif; ?>
          <div style="width:100px" class="ms-2">
            <label class="form-label mb-0" style="font-size:10px;color:#888">Bobot *</label>
            <input type="number" id="bobot-<?= $kpi['id'] ?>" name="bobot[<?= $kpi['id'] ?>]" class="form-control form-control-sm text-center bobot-induk-input"
                   style="font-size:11px;padding:2px 4px" step="0.0001" min="0" max="1"
                   value="<?= old("bobot.{$kpi['id']}", $fmtNum($bobotIndukVal)) ?>" placeholder="0.10">
          </div>
        </div>
        <?php else: ?>
        <div class="mb-1" style="font-size:11px;color:#888">
          Bobot Induk dihitung otomatis = jumlah Bobot seluruh Parameter Turunan di bawah ini.
        </div>
        <?php endif; ?>

        <?php foreach ($listTurunan as $t): ?>
        <?php
          $bulanTData = $targetBulananByTurunan[$t['id']] ?? [];
          $bobotTVal  = $turunanBobotIndexed[$t['id']] ?? null;
          $tSpecial   = $t['polarity'] === 'special';
        ?>
        <div class="mt-2 ps-3" style="border-left:3px solid #BDD7EE">
          <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
            <i class="ti ti-corner-down-right" style="color:#aaa;font-size:12px"></i>
            <span style="font-size:12px"><?= esc($t['nama_turunan']) ?></span>
            <span style="font-size:11px"><?= $polarityLabels[$t['polarity']] ?? '—' ?></span>
          </div>
          <div class="d-flex align-items-center gap-1 flex-wrap">
            <?php if (!$tSpecial): foreach ($namaBulan as $b => $label): ?>
              <div style="width:70px">
                <label class="form-label mb-0" style="font-size:10px;color:#888"><?= $label ?></label>
                <input type="number" id="turunan-target-<?= $t['id'] ?>-<?= $b ?>" name="turunan_target[<?= $t['id'] ?>][<?= $b ?>]" class="form-control form-control-sm text-center"
                       style="font-size:11px;padding:2px 4px" step="any" min="0"
                       value="<?= old("turunan_target.{$t['id']}.{$b}", $fmtNum($bulanTData[$b] ?? '')) ?>" placeholder="0">
              </div>
            <?php endforeach; else: ?>
              <span class="text-muted" style="font-size:11px">Target tidak berlaku untuk Special Scoring</span>
            <?php endif; ?>
            <div style="width:100px" class="ms-2">
              <label class="form-label mb-0" style="font-size:10px;color:#888">Bobot *</label>
              <input type="number" id="turunan-bobot-<?= $t['id'] ?>" name="turunan_bobot[<?= $t['id'] ?>]" class="form-control form-control-sm text-center bobot-turunan-input"
                     data-induk-id="<?= $kpi['id'] ?>"
                     style="font-size:11px;padding:2px 4px" step="0.0001" min="0" max="1"
                     value="<?= old("turunan_bobot.{$t['id']}", $fmtNum($bobotTVal)) ?>" placeholder="0.05">
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>

  <div class="d-flex gap-2 mt-2 mb-4">
    <button type="submit" class="btn btn-primary px-4">
      <i class="ti ti-device-floppy me-1"></i> Simpan Master Target
    </button>
    <a href="<?= base_url('master-target') ?>" class="btn btn-light border px-4">Kembali</a>
  </div>
</form>

<script>
function hitungTotalBobotMasterTarget() {
    let total = 0;
    document.querySelectorAll('.bobot-induk-input').forEach(i => {
        total += parseFloat(i.value) || 0;
    });
    document.querySelectorAll('.bobot-turunan-input').forEach(i => {
        total += parseFloat(i.value) || 0;
    });
    const pct = Math.round(total * 10000) / 100;
    document.getElementById('total-bobot-display').textContent = pct + '%';
    const alertBox = document.getElementById('alert-bobot');
    if (pct === 100) {
        alertBox.style.background  = '#E2EFDA';
        alertBox.style.borderColor = '#70AD47';
    } else {
        alertBox.style.background  = '#FCE4D6';
        alertBox.style.borderColor = '#C00000';
    }
}
document.querySelectorAll('.bobot-induk-input, .bobot-turunan-input').forEach(input => {
    input.addEventListener('input', hitungTotalBobotMasterTarget);
});
// Hitung ulang saat halaman dimuat — supaya banner Total Bobot langsung
// mencerminkan isian yang dikembalikan lewat old() setelah gagal validasi,
// bukan hanya nilai yang tersimpan di database sebelumnya.
hitungTotalBobotMasterTarget();

<?php $highlightId = session()->getFlashdata('highlight_id'); ?>
<?php if ($highlightId): ?>
// Fokus & sorot langsung ke parameter yang gagal validasi saat Simpan —
// supaya Admin tidak perlu mencari sendiri di antara puluhan input.
(function () {
    var el = document.getElementById(<?= json_encode($highlightId) ?>);
    if (!el) return;
    el.classList.add('is-invalid');
    el.style.borderColor = '#C00000';
    el.style.boxShadow = '0 0 0 2px rgba(192,0,0,.25)';
    el.scrollIntoView({behavior: 'smooth', block: 'center'});
    el.focus();
})();
<?php endif; ?>
</script>

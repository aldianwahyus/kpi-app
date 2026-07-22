<?php
$calculator = new \App\Services\KpiCalculationService();
$gradeInfo  = $calculator->getGradeInfo();
?>
<div class="card border-0 shadow-sm mt-3">
  <div class="card-header py-2"
       style="background:#E6F1FB">
    <span class="fw-semibold" style="color:#1F4E79;font-size:13px">
      <i class="ti ti-award me-1"></i> Panduan Klasifikasi Grade KPI
    </span>
  </div>
  <div class="card-body p-0">
    <table class="table table-sm align-middle mb-0"
           style="font-size:13px">
      <thead style="background:#f8fafc">
        <tr>
          <th class="text-center" style="width:60px">Grade</th>
          <th style="width:120px">Predikat</th>
          <th style="width:120px" class="text-center">Range Nilai</th>
          <th>Deskripsi</th>
          <th style="width:200px">Tindak Lanjut</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($gradeInfo as $g => $info): ?>
        <tr>
          <td class="text-center">
            <span class="badge fw-bold"
                  style="background:<?= $info['bg'] ?>;
                         color:<?= $info['color'] ?>;
                         font-size:16px;
                         padding:6px 14px;
                         border-radius:6px">
              <?= $g ?>
            </span>
          </td>
          <td class="fw-semibold"
              style="color:<?= $info['color'] == '#FFFFFF'
                               ? $info['bg'] : $info['color'] ?>">
            <?= $info['label'] ?>
          </td>
          <td class="text-center">
            <span class="badge"
                  style="background:<?= $info['bg'] ?>;
                         color:<?= $info['color'] ?>;
                         font-size:12px">
              <?= $info['range'] ?>
            </span>
          </td>
          <td style="color:#555"><?= $info['desc'] ?></td>
          <td style="font-size:12px;color:#888">
            <?php
            $tindakLanjut = [
                'IS' => 'Pertahankan & jadikan role model',
                'SB' => 'Kembangkan potensi lebih lanjut',
                'B'  => 'Coaching & monitoring berkala',
                'C'  => 'Performance Improvement Plan (PIP)',
            ];
            echo $tindakLanjut[$g] ?? '—';
            ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
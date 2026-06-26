<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h5 class="mb-0 fw-semibold" style="color:#1F4E79">
      <i class="ti ti-building-community me-1"></i> Data Unit Kerja
    </h5>
    <small class="text-muted">
      Kelola Divisi, Kantor Cabang, dan Kantor Cabang Pembantu
    </small>
  </div>
  <a href="<?= base_url('master/unit-kerja/create') ?>"
     class="btn btn-primary btn-sm">
    <i class="ti ti-plus me-1"></i> Tambah Unit Kerja
  </a>
</div>

<?php
$dir_colors = [
    0 => ['bg'=>'#E6F1FB','border'=>'#2E75B6','text'=>'#1F4E79'],
    1 => ['bg'=>'#EAF3DE','border'=>'#70AD47','text'=>'#375623'],
    2 => ['bg'=>'#FFF3CD','border'=>'#BF9000','text'=>'#7F6000'],
    3 => ['bg'=>'#F3E5F5','border'=>'#9B59B6','text'=>'#5C2A6B'],
    4 => ['bg'=>'#FCE4D6','border'=>'#E05D1C','text'=>'#7B2D0A'],
];
$dir_i = 0;
?>

<?php foreach ($grouped as $nama_dir => $divisis): ?>
<?php $c = $dir_colors[$dir_i % 5]; $dir_i++; ?>
<div class="card mb-3 border-0 shadow-sm">
  <div class="card-header py-2 d-flex align-items-center justify-content-between"
       style="background:<?= $c['bg'] ?>;border-left:4px solid <?= $c['border'] ?>">
    <span class="fw-semibold" style="color:<?= $c['text'] ?>;font-size:13px">
      <i class="ti ti-building me-1"></i><?= esc($nama_dir) ?>
    </span>
    <span class="badge" style="background:<?= $c['border'] ?>;font-size:11px">
      <?= count($divisis) ?> unit kerja
    </span>
  </div>
  <div class="card-body p-0">
    <table class="table table-sm table-hover align-middle mb-0"
           style="font-size:13px">
      <thead style="background:#f8fafc">
        <tr>
          <th style="width:120px">Kode</th>
          <th>Nama Unit Kerja</th>
          <th>Kepala / PJ</th>
          <th class="text-center" style="width:80px">Status</th>
          <th class="text-center" style="width:100px">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($divisis as $d): ?>
        <tr>
          <td>
            <code style="font-size:11px;background:#f0f4ff;
                         padding:2px 6px;border-radius:4px;
                         color:#2E75B6">
              <?= esc($d['kode']) ?>
            </code>
          </td>
          <td class="fw-semibold"><?= esc($d['nama']) ?></td>
          <td class="text-muted">
            <?= esc($d['kepala_divisi'] ?? '—') ?>
          </td>
          <td class="text-center">
            <a href="<?= base_url("master/unit-kerja/toggle/{$d['id']}") ?>"
               class="badge text-decoration-none"
               style="font-size:11px;
                 background:<?= $d['is_active']?'#C6EFCE':'#FCE4D6' ?>;
                 color:<?= $d['is_active']?'#375623':'#C00000' ?>">
              <?= $d['is_active'] ? 'Aktif' : 'Nonaktif' ?>
            </a>
          </td>
          <td class="text-center">
            <div class="d-flex gap-1 justify-content-center">
              <a href="<?= base_url("master/unit-kerja/edit/{$d['id']}") ?>"
                 class="btn btn-outline-primary"
                 style="padding:2px 8px;font-size:11px">
                <i class="ti ti-edit"></i>
              </a>
              <a href="<?= base_url("master/unit-kerja/delete/{$d['id']}") ?>"
                 class="btn btn-outline-danger"
                 style="padding:2px 8px;font-size:11px"
                 onclick="return confirmAction(event, { title: 'Hapus Unit Kerja', text: 'Hapus unit kerja ini?', confirmText: 'Ya, Hapus', danger: true })">
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
    <i class="ti ti-building-off fs-1 d-block mb-2"></i>
    Belum ada unit kerja. Tambah sekarang!
  </div>
<?php endif; ?>
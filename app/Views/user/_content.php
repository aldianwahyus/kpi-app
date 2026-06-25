<div class="d-flex align-items-center justify-content-between mb-3">
  <div>
    <h5 class="mb-0 fw-semibold" style="color:#1F4E79">
      <i class="ti ti-users-cog me-1"></i> Manajemen User
    </h5>
    <small class="text-muted">
      Total <?= $total_users ?> user terdaftar
    </small>
  </div>
  <a href="<?= base_url('master/users/create') ?>"
     class="btn btn-primary btn-sm">
    <i class="ti ti-user-plus me-1"></i> Tambah User
  </a>
</div>

<?php
$role_config = [
    'admin'   => ['label'=>'Admin',      'bg'=>'#DBEAFE','color'=>'#1D4ED8','border'=>'#2E75B6'],
    'hr'      => ['label'=>'HR Manager', 'bg'=>'#D1FAE5','color'=>'#065F46','border'=>'#70AD47'],
    'manajer' => ['label'=>'Manajer',    'bg'=>'#FEF3C7','color'=>'#92400E','border'=>'#BF9000'],
    'pegawai' => ['label'=>'Pegawai',    'bg'=>'#F3E8FF','color'=>'#6B21A8','border'=>'#9B59B6'],
];
$role_order = ['admin','hr','manajer','pegawai'];
?>

<?php foreach ($role_order as $role): ?>
<?php if (empty($grouped[$role])) continue; ?>
<?php $rc = $role_config[$role]; ?>
<div class="card mb-3 border-0 shadow-sm">
  <div class="card-header py-2 d-flex align-items-center
              justify-content-between"
       style="background:<?= $rc['bg'] ?>;
              border-left:4px solid <?= $rc['border'] ?>">
    <span class="fw-semibold"
          style="color:<?= $rc['color'] ?>;font-size:13px">
      <i class="ti ti-shield me-1"></i><?= $rc['label'] ?>
    </span>
    <span class="badge"
          style="background:<?= $rc['border'] ?>;font-size:11px">
      <?= count($grouped[$role]) ?> user
    </span>
  </div>
  <div class="card-body p-0">
    <table class="table table-sm table-hover align-middle mb-0"
           style="font-size:13px">
      <thead style="background:#f8fafc">
        <tr>
          <th>Nama</th>
          <th>Email</th>
          <th>Pegawai Terhubung</th>
          <th>Divisi</th>
          <th>Last Login</th>
          <th class="text-center">Status</th>
          <th class="text-center" style="width:140px">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($grouped[$role] as $u): ?>
        <tr class="<?= !$u['is_active'] ? 'text-muted' : '' ?>">
          <td>
            <div class="fw-semibold"><?= esc($u['nama']) ?></div>
          </td>
          <td>
            <code style="font-size:11px;color:#555">
              <?= esc($u['email']) ?>
            </code>
          </td>
          <td>
            <?php if ($u['nama_pegawai']): ?>
              <div style="font-size:12px"><?= esc($u['nama_pegawai']) ?></div>
              <small class="text-muted"><?= esc($u['jabatan'] ?? '') ?></small>
            <?php else: ?>
              <span class="text-muted" style="font-size:12px">—</span>
            <?php endif; ?>
          </td>
          <td style="font-size:12px;color:#888">
            <?= esc($u['divisi'] ?? '—') ?>
          </td>
          <td style="font-size:12px;color:#888">
            <?= $u['last_login']
                ? date('d M Y H:i', strtotime($u['last_login']))
                : 'Belum pernah' ?>
          </td>
          <td class="text-center">
            <a href="<?= base_url("master/users/toggle/{$u['id']}") ?>"
               class="badge text-decoration-none"
               style="font-size:11px;
                 background:<?= $u['is_active']?'#C6EFCE':'#FCE4D6' ?>;
                 color:<?= $u['is_active']?'#375623':'#C00000' ?>">
              <?= $u['is_active'] ? 'Aktif' : 'Nonaktif' ?>
            </a>
          </td>
          <td class="text-center">
            <div class="d-flex gap-1 justify-content-center">
              <a href="<?= base_url("master/users/edit/{$u['id']}") ?>"
                 class="btn btn-outline-primary"
                 style="padding:2px 8px;font-size:11px"
                 title="Edit">
                <i class="ti ti-edit"></i>
              </a>
              <a href="<?= base_url("master/users/reset/{$u['id']}") ?>"
                 class="btn btn-outline-warning"
                 style="padding:2px 8px;font-size:11px"
                 title="Reset Password"
                 onclick="return confirm('Reset password ke default?')">
                <i class="ti ti-key"></i>
              </a>
              <?php if ($u['id'] != session()->get('user_id')): ?>
              <a href="<?= base_url("master/users/delete/{$u['id']}") ?>"
                 class="btn btn-outline-danger"
                 style="padding:2px 8px;font-size:11px"
                 title="Hapus"
                 onclick="return confirm('Hapus user <?= esc($u['nama']) ?>?')">
                <i class="ti ti-trash"></i>
              </a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endforeach; ?>